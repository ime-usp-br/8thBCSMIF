<?php

namespace App\Livewire\Admin;

use App\Models\EnrollmentProof;
use App\Models\Registration;
use Livewire\Component;
use Livewire\WithPagination;

class RegistrationsList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterEventCode = '';

    public string $filterEnrollmentProofStatus = '';

    public string $filterPaymentStatus = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    public string $filterFeeMin = '';

    public string $filterFeeMax = '';

    public string $filterStudentCategory = '';

    public string $filterCountry = '';

    public string $filterTransport = '';

    public string $filterMinFee = '';

    public string $filterMaxFee = '';

    public function render(): \Illuminate\View\View
    {
        $registrations = Registration::query()
            ->with(['user', 'events', 'payments', 'enrollmentProof'])
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($this->filterEventCode, function ($query, $eventCode) {
                $query->whereHas('events', function ($eventQuery) use ($eventCode) {
                    $eventQuery->where('code', $eventCode);
                });
            })
            ->when($this->filterEnrollmentProofStatus, function ($query, $status) {
                if ($status === 'none') {
                    $query->whereDoesntHave('enrollmentProof');
                } else {
                    $query->whereHas('enrollmentProof', function ($proofQuery) use ($status) {
                        $proofQuery->where('status', $status);
                    });
                }
            })
            ->when($this->filterPaymentStatus, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($this->filterDateFrom, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($this->filterDateTo, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->when($this->filterFeeMin, function ($query, $feeMin) {
                $query->where('fee', '>=', $feeMin);
            })
            ->when($this->filterFeeMax, function ($query, $feeMax) {
                $query->where('fee', '<=', $feeMax);
            })
            ->when($this->filterStudentCategory, function ($query, $category) {
                if ($category === 'student') {
                    $query->whereIn('registration_category_snapshot', ['undergrad_student', 'grad_student']);
                } else {
                    $query->where('registration_category_snapshot', $category);
                }
            })
            ->when($this->filterCountry, function ($query, $country) {
                if ($country === 'Brazil') {
                    $query->where('address_country', 'Brazil');
                } elseif ($country === 'OTHER') {
                    $query->where('address_country', '!=', 'Brazil');
                }
            })
            ->when($this->filterTransport, function ($query, $transport) {
                switch ($transport) {
                    case 'gru':
                        $query->where('needs_transport_from_gru', true);
                        break;
                    case 'usp':
                        $query->where('needs_transport_from_usp', true);
                        break;
                    case 'both':
                        $query->where('needs_transport_from_gru', true)
                            ->where('needs_transport_from_usp', true);
                        break;
                    case 'none':
                        $query->where('needs_transport_from_gru', false)
                            ->where('needs_transport_from_usp', false);
                        break;
                }
            })
            ->when($this->filterMinFee, function ($query, $minFee) {
                // Use a calculated field or add fee calculation logic
                $query->whereRaw('(SELECT SUM(CAST(price AS DECIMAL(10,2))) FROM fees WHERE fees.event_code IN (SELECT event_code FROM event_registration WHERE event_registration.registration_id = registrations.id)) >= ?', [$minFee]);
            })
            ->when($this->filterMaxFee, function ($query, $maxFee) {
                // Use a calculated field or add fee calculation logic
                $query->whereRaw('(SELECT SUM(CAST(price AS DECIMAL(10,2))) FROM fees WHERE fees.event_code IN (SELECT event_code FROM event_registration WHERE event_registration.registration_id = registrations.id)) <= ?', [$maxFee]);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.registrations-list', [
            'registrations' => $registrations,
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterEventCode(): void
    {
        $this->resetPage();
    }

    public function updatedFilterEnrollmentProofStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterPaymentStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedFilterFeeMin(): void
    {
        $this->resetPage();
    }

    public function updatedFilterFeeMax(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStudentCategory(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCountry(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTransport(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMinFee(): void
    {
        $this->resetPage();
    }

    public function updatedFilterMaxFee(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'filterEventCode',
            'filterEnrollmentProofStatus',
            'filterPaymentStatus',
            'filterDateFrom',
            'filterDateTo',
            'filterFeeMin',
            'filterFeeMax',
            'filterStudentCategory',
            'filterCountry',
            'filterTransport',
            'filterMinFee',
            'filterMaxFee',
        ]);
        $this->resetPage();
    }

    public function clearAllFilters(): void
    {
        $this->clearFilters();
    }

    public function approveDocument(int $enrollmentProofId): void
    {
        $enrollmentProof = EnrollmentProof::findOrFail($enrollmentProofId);
        $enrollmentProof->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('success', __('Document approved successfully.'));
    }

    public function rejectDocument(int $enrollmentProofId): void
    {
        $enrollmentProof = EnrollmentProof::findOrFail($enrollmentProofId);
        $enrollmentProof->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => 'Rejected from registration list',
        ]);

        session()->flash('success', __('Document rejected.'));
    }

    public function exportSelected(): void
    {
        // TODO: Implement export functionality
        session()->flash('info', __('Export functionality coming soon.'));
    }

    public function markDocumentsReviewed(): void
    {
        // TODO: Implement batch review functionality
        session()->flash('info', __('Batch review functionality coming soon.'));
    }

    public function sendBulkEmail(): void
    {
        // TODO: Implement bulk email functionality
        session()->flash('info', __('Bulk email functionality coming soon.'));
    }
}
