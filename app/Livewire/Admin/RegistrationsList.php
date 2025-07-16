<?php

namespace App\Livewire\Admin;

use App\Models\Registration;
use Livewire\Component;
use Livewire\WithPagination;

class RegistrationsList extends Component
{
    use WithPagination;

    public string $filterEventCode = '';

    public string $filterEnrollmentProofStatus = '';

    public function render(): \Illuminate\View\View
    {
        $registrations = Registration::query()
            ->with(['user', 'events', 'payments', 'enrollmentProof'])
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
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.registrations-list', [
            'registrations' => $registrations,
        ]);
    }

    public function updatedFilterEventCode(): void
    {
        $this->resetPage();
    }

    public function updatedFilterEnrollmentProofStatus(): void
    {
        $this->resetPage();
    }
}
