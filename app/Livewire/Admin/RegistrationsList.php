<?php

namespace App\Livewire\Admin;

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

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterEventCode', 'filterEnrollmentProofStatus', 'filterPaymentStatus', 'filterDateFrom', 'filterDateTo', 'filterFeeMin', 'filterFeeMax']);
        $this->resetPage();
    }
}
