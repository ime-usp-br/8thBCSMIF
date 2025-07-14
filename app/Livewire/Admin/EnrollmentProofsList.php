<?php

namespace App\Livewire\Admin;

use App\Models\EnrollmentProof;
use Livewire\Component;
use Livewire\WithPagination;

class EnrollmentProofsList extends Component
{
    use WithPagination;

    public string $filterStatus = '';

    public function render(): \Illuminate\View\View
    {
        $enrollmentProofs = EnrollmentProof::query()
            ->with(['registration.user', 'registration.events', 'approvedBy'])
            ->when($this->filterStatus, function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('livewire.admin.enrollment-proofs-list', [
            'enrollmentProofs' => $enrollmentProofs,
        ]);
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }
}
