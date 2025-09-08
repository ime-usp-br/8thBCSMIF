<?php

namespace App\Livewire\Admin;

use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * AC1: ApprovalQueue Livewire Component
 *
 * Displays a unified queue of pending payment and enrollment proof validations.
 * Provides asynchronous approve/reject actions without page reload.
 */
class ApprovalQueue extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterType = ''; // all, payment, enrollment

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    /**
     * AC1: Render the approval queue with pending items
     * Shows both payment proofs and enrollment proofs requiring validation
     */
    public function render(): \Illuminate\View\View
    {
        $pendingItems = $this->getPendingApprovalItems();

        return view('livewire.admin.approval-queue', [
            'pendingItems' => $pendingItems,
            'totalPending' => $pendingItems->count(),
        ]);
    }

    /**
     * Get all pending approval items (payments, enrollment proofs, and exemptions)
     * Returns a unified collection with dual-validation detection and visual grouping support
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function getPendingApprovalItems(): Collection
    {
        // Get pending payment proofs
        $pendingPayments = Payment::with(['registration.user', 'registration.events'])
            ->where('status', Payment::STATUS_PENDING_APPROVAL)
            ->when($this->search, function ($query, $search) {
                $query->whereHas('registration', function ($regQuery) use ($search) {
                    $regQuery->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($this->filterType === 'payment', function ($query) {
                // Only payment proofs
                return $query;
            })
            ->get();

        // AC3: Get registrations eligible for fee exemption (pending status with no payments)
        $exemptionEligible = Registration::with(['user', 'events', 'payments'])
            ->where('status', Registration::STATUS_PENDING)
            ->whereDoesntHave('payments') // No payments created yet
            ->when($this->search, function ($query, $search) {
                $query->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            })
            ->when($this->filterType === 'exemption', function ($query) {
                // Only exemption eligible registrations
                return $query;
            })
            ->get();

        // Get pending enrollment proofs
        $pendingEnrollments = EnrollmentProof::with(['registration.user', 'registration.events'])
            ->where('status', EnrollmentProof::STATUS_PENDING_APPROVAL)
            ->when($this->search, function ($query, $search) {
                $query->whereHas('registration', function ($regQuery) use ($search) {
                    $regQuery->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($this->filterType === 'enrollment', function ($query) {
                // Only enrollment proofs
                return $query;
            })
            ->get();

        // Detect dual-validation scenarios by registration ID
        $paymentRegistrationIds = $pendingPayments->pluck('registration_id')->unique();
        $enrollmentRegistrationIds = $pendingEnrollments->pluck('registration_id')->unique();
        $dualValidationRegistrationIds = $paymentRegistrationIds->intersect($enrollmentRegistrationIds);

        // Map payment items with dual-validation metadata
        $mappedPayments = $pendingPayments->map(function ($payment) use ($dualValidationRegistrationIds) {
            $events = $payment->registration->events;
            $requiresDualValidation = $dualValidationRegistrationIds->contains($payment->registration_id);

            return [
                'id' => $payment->id,
                'type' => 'payment',
                'type_label' => __('Payment Proof'),
                'registration' => $payment->registration,
                'registration_id' => $payment->registration_id,
                'participant_name' => $payment->registration->full_name,
                'participant_email' => $payment->registration->email ?? ($payment->registration->user->email ?? null),
                'events' => $events,
                'amount' => $payment->amount,
                'created_at' => $payment->created_at,
                'has_file' => ! empty($payment->payment_proof_path),
                'file_path' => $payment->payment_proof_path,
                'requires_dual_validation' => $requiresDualValidation,
                'dual_validation_type' => $requiresDualValidation ? 'payment_first' : null,
                'registration_category' => $payment->registration->registration_category_snapshot,
            ];
        });

        // Map enrollment items with dual-validation metadata
        $mappedEnrollments = $pendingEnrollments->map(function ($enrollmentProof) use ($dualValidationRegistrationIds) {
            $events = $enrollmentProof->registration->events;
            $requiresDualValidation = $dualValidationRegistrationIds->contains($enrollmentProof->registration_id);

            return [
                'id' => $enrollmentProof->id,
                'type' => 'enrollment',
                'type_label' => __('Enrollment Proof'),
                'registration' => $enrollmentProof->registration,
                'registration_id' => $enrollmentProof->registration_id,
                'participant_name' => $enrollmentProof->registration->full_name,
                'participant_email' => $enrollmentProof->registration->email ?? ($enrollmentProof->registration->user->email ?? null),
                'events' => $events,
                'amount' => null, // No amount for enrollment proofs
                'created_at' => $enrollmentProof->created_at,
                'has_file' => ! empty($enrollmentProof->file_path),
                'file_path' => $enrollmentProof->file_path,
                'original_filename' => $enrollmentProof->original_filename,
                'requires_dual_validation' => $requiresDualValidation,
                'dual_validation_type' => $requiresDualValidation ? 'enrollment_second' : null,
                'registration_category' => $enrollmentProof->registration->registration_category_snapshot,
            ];
        });

        // AC3: Map exemption eligible registrations
        $mappedExemptions = $exemptionEligible->map(function ($registration) {
            $events = $registration->events;

            return [
                'id' => $registration->id,
                'type' => 'exemption',
                'type_label' => __('Fee Exemption'),
                'registration' => $registration,
                'registration_id' => $registration->id,
                'participant_name' => $registration->full_name,
                'participant_email' => $registration->email ?? ($registration->user->email ?? null),
                'events' => $events,
                'amount' => $registration->calculateCorrectTotalFee(),
                'created_at' => $registration->created_at,
                'has_file' => false, // No file for exemptions
                'file_path' => null,
                'requires_dual_validation' => false,
                'dual_validation_type' => null,
                'registration_category' => $registration->registration_category_snapshot,
            ];
        });

        // Filter by type if specified
        $allItems = collect();

        if ($this->filterType === '' || $this->filterType === 'payment') {
            $allItems = $allItems->concat($mappedPayments);
        }

        if ($this->filterType === '' || $this->filterType === 'enrollment') {
            $allItems = $allItems->concat($mappedEnrollments);
        }

        // AC3: Include exemption eligible registrations
        if ($this->filterType === '' || $this->filterType === 'exemption') {
            $allItems = $allItems->concat($mappedExemptions);
        }

        // Sort the combined collection - dual validation entries grouped by registration
        /** @var Collection<int, array<string, mixed>> $sortedItems */
        $sortedItems = $allItems->sortBy([
            ['requires_dual_validation', 'desc'], // Dual validations first
            ['registration_id', 'asc'], // Group by registration
            ['dual_validation_type', 'asc'], // Payment first, then enrollment
            [$this->sortBy, $this->sortDirection],
        ])->values();

        return $sortedItems;
    }

    /**
     * AC1: Quick approve action - removes item from queue asynchronously
     * AC3: Extended to handle exemption approvals
     */
    public function quickApprove(string $type, int $id): void
    {
        try {
            if ($type === 'payment') {
                $payment = Payment::findOrFail($id);
                $payment->update(['status' => Payment::STATUS_APPROVED]);

                session()->flash('success', __('Payment proof approved successfully.'));
            } elseif ($type === 'enrollment') {
                $enrollmentProof = EnrollmentProof::findOrFail($id);
                $enrollmentProof->update([
                    'status' => EnrollmentProof::STATUS_APPROVED,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                session()->flash('success', __('Enrollment proof approved successfully.'));
            } elseif ($type === 'exemption') {
                // AC3: Handle fee exemption approval
                $this->approveExemption($id);

                return; // approveExemption handles its own flash messages and refresh
            }

            // Refresh the component data
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            session()->flash('error', __('Error approving item: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * AC3: Approve a fee exemption by calling the AdminRegistrationController
     */
    public function approveExemption(int $registrationId): void
    {
        try {
            $registration = Registration::findOrFail($registrationId);

            // Create instance of AdminRegistrationController and call approve method
            $controller = new \App\Http\Controllers\AdminRegistrationController;
            $request = request(); // Use current request context

            // Call the approve method which handles exemption logic
            $response = $controller->approve($request, $registration);
            $content = $response->getContent();
            $responseData = $content !== false ? json_decode($content, true) : null;

            if (is_array($responseData) && ($responseData['success'] ?? false)) {
                session()->flash('success', $responseData['message'] ?? __('Fee exemption approved successfully.'));
            } else {
                session()->flash('error', is_array($responseData) ? ($responseData['message'] ?? __('Error approving exemption.')) : __('Error approving exemption.'));
            }

            // Refresh the component data
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            session()->flash('error', __('Error approving exemption: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * AC1: Quick reject action - removes item from queue asynchronously
     * For now uses a default rejection reason, AC2 will add modal with custom reason
     */
    public function quickReject(string $type, int $id): void
    {
        try {
            $defaultReason = __('Quick rejection from approval queue');

            if ($type === 'payment') {
                $payment = Payment::findOrFail($id);
                $payment->update([
                    'status' => Payment::STATUS_REJECTED,
                    'notes' => $defaultReason,
                ]);

                session()->flash('success', __('Payment proof rejected successfully.'));
            } elseif ($type === 'enrollment') {
                $enrollmentProof = EnrollmentProof::findOrFail($id);
                $enrollmentProof->update([
                    'status' => EnrollmentProof::STATUS_REJECTED,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'rejection_reason' => $defaultReason,
                ]);

                session()->flash('success', __('Enrollment proof rejected successfully.'));
            }

            // Refresh the component data
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            session()->flash('error', __('Error rejecting item: :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Update search and reset pagination
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Update filter type and reset pagination
     */
    public function updatedFilterType(): void
    {
        $this->resetPage();
    }

    /**
     * Clear all filters
     */
    public function clearFilters(): void
    {
        $this->reset(['search', 'filterType']);
        $this->resetPage();
    }
}
