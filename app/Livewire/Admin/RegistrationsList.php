<?php

namespace App\Livewire\Admin;

use App\Models\Registration;
use Livewire\Component;
use Livewire\WithPagination;

class RegistrationsList extends Component
{
    use WithPagination;

    public string $filterEventCode = '';

    public string $filterPaymentStatus = '';

    public function render(): \Illuminate\View\View
    {
        $registrations = Registration::query()
            ->with(['user', 'events'])
            ->when($this->filterEventCode, function ($query, $eventCode) {
                $query->whereHas('events', function ($eventQuery) use ($eventCode) {
                    $eventQuery->where('code', $eventCode);
                });
            })
            ->when($this->filterPaymentStatus, function ($query, $paymentStatus) {
                $query->where('payment_status', $paymentStatus);
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

    public function updatedFilterPaymentStatus(): void
    {
        $this->resetPage();
    }
}
