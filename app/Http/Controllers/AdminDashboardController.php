<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    /**
     * Show the admin dashboard with metrics overview.
     */
    public function index(): View
    {
        // Placeholder metrics data structure for widgets
        $metrics = [
            'total_registrations' => [
                'count' => 142,
                'trend' => 'up',
                'change_percent' => 12.5,
            ],
            'registrations_by_category' => [
                'undergrad_student' => 45,
                'grad_student' => 38,
                'professor' => 28,
                'professional' => 31,
            ],
            'pending_approvals' => [
                'payment_proofs' => 15,
                'enrollment_proofs' => 8,
            ],
            'revenue' => [
                'confirmed' => 45750.00,
                'pending' => 12300.00,
                'currency' => 'BRL',
            ],
            'transport_needs' => [
                'from_usp' => 23,
                'from_gru' => 18,
            ],
        ];

        return view('admin.dashboard', compact('metrics'));
    }
}
