<?php

namespace App\Livewire\Admin;

use App\Models\Registration;
use Livewire\Component;
use Livewire\WithPagination;

class RegistrationsList extends Component
{
    use WithPagination;

    public string $filterEventCode = '';

    public function render(): \Illuminate\View\View
    {
        $registrations = Registration::query()
            ->with(['user', 'events', 'payments'])
            ->when($this->filterEventCode, function ($query, $eventCode) {
                $query->whereHas('events', function ($eventQuery) use ($eventCode) {
                    $eventQuery->where('code', $eventCode);
                });
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
}
