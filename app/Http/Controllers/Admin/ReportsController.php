<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentProof;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(): View
    {
        $enrollmentProofsStats = $this->getEnrollmentProofsStats();
        $paymentsStats = $this->getPaymentsStats();
        $autoApprovedStats = $this->getAutoApprovedStats();

        return view('admin.reports.index', compact(
            'enrollmentProofsStats',
            'paymentsStats',
            'autoApprovedStats'
        ));
    }

    public function enrollmentProofs(Request $request): View
    {
        $filterStatus = $request->get('status', '');
        $filterDateFrom = $request->get('date_from', '');
        $filterDateTo = $request->get('date_to', '');

        $query = EnrollmentProof::query()
            ->with(['registration.user', 'registration.events', 'approvedBy'])
            ->when($filterStatus, function ($query, mixed $status) {
                $query->where('status', $status);
            })
            ->when($filterDateFrom, function ($query, mixed $date) {
                assert(is_string($date));
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($filterDateTo, function ($query, mixed $date) {
                assert(is_string($date));
                $query->whereDate('created_at', '<=', $date);
            });

        $enrollmentProofs = $query->orderBy('created_at', 'desc')->paginate(20);
        $stats = $this->getEnrollmentProofsStats();

        return view('admin.reports.enrollment-proofs', compact(
            'enrollmentProofs',
            'stats',
            'filterStatus',
            'filterDateFrom',
            'filterDateTo'
        ));
    }

    public function payments(Request $request): View
    {
        $filterStatus = $request->get('status', '');
        $filterAmountFrom = $request->get('amount_from', '');
        $filterAmountTo = $request->get('amount_to', '');
        $filterDateFrom = $request->get('date_from', '');
        $filterDateTo = $request->get('date_to', '');

        $query = Payment::query()
            ->with(['registration.user', 'registration.events', 'events'])
            ->when($filterStatus, function ($query, mixed $status) {
                $query->where('status', $status);
            })
            ->when($filterAmountFrom, function ($query, mixed $amount) {
                $query->where('amount', '>=', $amount);
            })
            ->when($filterAmountTo, function ($query, mixed $amount) {
                $query->where('amount', '<=', $amount);
            })
            ->when($filterDateFrom, function ($query, mixed $date) {
                assert(is_string($date));
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($filterDateTo, function ($query, mixed $date) {
                assert(is_string($date));
                $query->whereDate('created_at', '<=', $date);
            });

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);
        $stats = $this->getPaymentsStats();

        return view('admin.reports.payments', compact(
            'payments',
            'stats',
            'filterStatus',
            'filterAmountFrom',
            'filterAmountTo',
            'filterDateFrom',
            'filterDateTo'
        ));
    }

    public function autoApproved(Request $request): View
    {
        $filterDateFrom = $request->get('date_from', '');
        $filterDateTo = $request->get('date_to', '');

        $query = Payment::query()
            ->with(['registration.user', 'registration.events', 'events'])
            ->where('status', 'approved')
            ->where('amount', '0.00')
            ->when($filterDateFrom, function ($query, mixed $date) {
                assert(is_string($date));
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($filterDateTo, function ($query, mixed $date) {
                assert(is_string($date));
                $query->whereDate('created_at', '<=', $date);
            });

        $autoApprovedPayments = $query->orderBy('created_at', 'desc')->paginate(20);
        $stats = $this->getAutoApprovedStats();

        return view('admin.reports.auto-approved', compact(
            'autoApprovedPayments',
            'stats',
            'filterDateFrom',
            'filterDateTo'
        ));
    }

    /**
     * @return array<string, int>
     */
    private function getEnrollmentProofsStats(): array
    {
        $total = EnrollmentProof::count();
        $pending = EnrollmentProof::where('status', 'pending_approval')->count();
        $approved = EnrollmentProof::where('status', 'approved')->count();
        $rejected = EnrollmentProof::where('status', 'rejected')->count();

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
        ];
    }

    /**
     * @return array<string, int|float|string>
     */
    private function getPaymentsStats(): array
    {
        $total = Payment::count();
        $pending = Payment::where('status', 'pending')->count();
        $approved = Payment::where('status', 'approved')->count();
        $rejected = Payment::where('status', 'rejected')->count();
        $totalAmount = Payment::sum('amount');
        $averageAmount = Payment::avg('amount');

        return [
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'total_amount' => $totalAmount ?: 0,
            'average_amount' => $averageAmount ?: 0.0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function getAutoApprovedStats(): array
    {
        $total = Payment::where('status', 'approved')
            ->where('amount', '0.00')
            ->count();

        $graduateStudents = Payment::where('status', 'approved')
            ->where('amount', '0.00')
            ->whereHas('registration', function ($query) {
                $query->where('registration_category_snapshot', 'grad_student');
            })
            ->count();

        $workshopRegistrations = Payment::where('status', 'approved')
            ->where('amount', '0.00')
            ->whereHas('events', function ($query) {
                $query->where('is_main_conference', false);
            })
            ->count();

        return [
            'total' => $total,
            'graduate_students' => $graduateStudents,
            'workshop_registrations' => $workshopRegistrations,
        ];
    }
}
