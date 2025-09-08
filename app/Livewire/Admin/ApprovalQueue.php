<?php

namespace App\Livewire\Admin;

use App\Models\EnrollmentProof;
use App\Models\Payment;
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
     * Get all pending approval items (payments and enrollment proofs)
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

        // Filter by type if specified
        $allItems = collect();

        if ($this->filterType === '' || $this->filterType === 'payment') {
            $allItems = $allItems->concat($mappedPayments);
        }

        if ($this->filterType === '' || $this->filterType === 'enrollment') {
            $allItems = $allItems->concat($mappedEnrollments);
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
            }

            // Refresh the component data
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            session()->flash('error', __('Error approving item: :message', ['message' => $e->getMessage()]));
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
