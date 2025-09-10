<?php

namespace App\Livewire\Admin;

use App\Models\EnrollmentProof;
use App\Models\Registration;
use App\Services\RegistrationExportService;
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

    // CSV Export modal properties
    public bool $showExportModal = false;

    /** @var array<string> */
    public array $selectedColumns = [];

    /** @var array<string, array<string, string>> */
    public array $availableColumns = [];

    /** @var array<string, string> */
    public array $columnGroups = [];

    public function mount(RegistrationExportService $exportService): void
    {
        // Initialize export data
        $this->availableColumns = $exportService->getAvailableColumns();
        $this->columnGroups = $exportService->getColumnGroups();

        // Default selected columns (basic info)
        $this->selectedColumns = ['id', 'full_name', 'email', 'status', 'created_at'];
    }

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

    /**
     * Open export modal with available columns
     */
    public function openExportModal(): void
    {
        $this->showExportModal = true;
    }

    /**
     * Close export modal and reset state
     */
    public function closeExportModal(): void
    {
        $this->showExportModal = false;
        $this->resetErrorBag();
    }

    /**
     * Toggle all columns selection
     */
    public function selectAllColumns(): void
    {
        $allColumns = [];
        foreach ($this->availableColumns as $group => $columns) {
            $allColumns = array_merge($allColumns, array_keys($columns));
        }
        $this->selectedColumns = $allColumns;
    }

    /**
     * Deselect all columns
     */
    public function deselectAllColumns(): void
    {
        $this->selectedColumns = [];
    }

    /**
     * Toggle columns for a specific group
     */
    public function toggleGroupColumns(string $group): void
    {
        if (! isset($this->availableColumns[$group])) {
            return;
        }

        $groupColumns = array_keys($this->availableColumns[$group]);
        $selectedInGroup = array_intersect($this->selectedColumns, $groupColumns);

        if (count($selectedInGroup) === count($groupColumns)) {
            // All selected, deselect all
            $this->selectedColumns = array_diff($this->selectedColumns, $groupColumns);
        } else {
            // Some or none selected, select all
            $this->selectedColumns = array_unique(array_merge($this->selectedColumns, $groupColumns));
        }
    }

    /**
     * Check if all columns in a group are selected
     */
    public function isGroupFullySelected(string $group): bool
    {
        if (! isset($this->availableColumns[$group])) {
            return false;
        }

        $groupColumns = array_keys($this->availableColumns[$group]);
        $selectedInGroup = array_intersect($this->selectedColumns, $groupColumns);

        return count($selectedInGroup) === count($groupColumns);
    }

    /**
     * Check if some columns in a group are selected
     */
    public function isGroupPartiallySelected(string $group): bool
    {
        if (! isset($this->availableColumns[$group])) {
            return false;
        }

        $groupColumns = array_keys($this->availableColumns[$group]);
        $selectedInGroup = array_intersect($this->selectedColumns, $groupColumns);

        return count($selectedInGroup) > 0 && count($selectedInGroup) < count($groupColumns);
    }

    /**
     * Export CSV with selected columns and current filters
     */
    public function exportCsv(): void
    {
        $this->validate([
            'selectedColumns' => 'required|array|min:1',
            'selectedColumns.*' => 'required|string',
        ], [
            'selectedColumns.required' => __('Please select at least one column to export.'),
            'selectedColumns.min' => __('Please select at least one column to export.'),
        ]);

        try {
            // Collect all current filter values
            $filters = [
                'search' => $this->search,
                'filterEventCode' => $this->filterEventCode,
                'filterEnrollmentProofStatus' => $this->filterEnrollmentProofStatus,
                'filterPaymentStatus' => $this->filterPaymentStatus,
                'filterDateFrom' => $this->filterDateFrom,
                'filterDateTo' => $this->filterDateTo,
                'filterFeeMin' => $this->filterFeeMin,
                'filterFeeMax' => $this->filterFeeMax,
                'filterStudentCategory' => $this->filterStudentCategory,
                'filterCountry' => $this->filterCountry,
                'filterTransport' => $this->filterTransport,
                'filterMinFee' => $this->filterMinFee,
                'filterMaxFee' => $this->filterMaxFee,
            ];

            // Redirect to export route with POST data
            $this->dispatch('export-csv', [
                'columns' => $this->selectedColumns,
                'filters' => $filters,
            ]);

            // Close modal on success
            $this->closeExportModal();

            session()->flash('success', __('CSV export initiated. Download should start shortly.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Error exporting CSV: :message', ['message' => $e->getMessage()]));
        }
    }

    public function exportSelected(): void
    {
        $this->openExportModal();
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
