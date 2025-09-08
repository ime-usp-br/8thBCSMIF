<?php

use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\EnrollmentProofController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RegistrationModificationController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome')->middleware('ensure.registration');
Route::view('workshops', 'workshops')->name('workshops')->middleware('ensure.registration');
Route::view('fees', 'fees')->name('fees')->middleware('ensure.registration');
Route::view('payment-info', 'payment-info')->name('payment-info')->middleware('ensure.registration');

Route::view('profile', 'profile')
    ->middleware(['auth', 'ensure.registration'])
    ->name('profile');

// Redirect dashboard to registrations.my for compatibility
Route::redirect('/dashboard', '/my-registration')->name('dashboard');

// Route for the event registration form
Volt::route('register-event', 'registration-form')
    ->middleware(['auth', 'verified'])
    ->name('register-event');

// Route for storing a new event registration
Route::post('/event-registrations', [RegistrationController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('event-registrations.store');

// Route for uploading payment proof (legacy route for backward compatibility)
Route::post('/event-registrations/{registration}/upload-proof', [RegistrationController::class, 'uploadProof'])
    ->middleware(['auth', 'verified'])
    ->name('event-registrations.upload-proof');

// Route for uploading payment proof to specific payment
Route::post('/payments/{payment}/upload-proof', [PaymentController::class, 'uploadProof'])
    ->middleware(['auth', 'verified'])
    ->name('payments.upload-proof');

// Route for downloading payment proof for specific payment
Route::get('/payments/{payment}/download-proof', [PaymentController::class, 'downloadProof'])
    ->middleware(['auth', 'verified'])
    ->name('payments.download-proof');

// Route for uploading enrollment proof to specific registration
Route::post('/enrollment-proofs/{registration}', [EnrollmentProofController::class, 'uploadProof'])
    ->middleware(['auth', 'verified'])
    ->name('enrollment-proofs.upload');

// Route for downloading enrollment proof for specific registration
Route::get('/enrollment-proofs/{registration}/download', [EnrollmentProofController::class, 'downloadProof'])
    ->middleware(['auth', 'verified'])
    ->name('enrollment-proofs.download');

// AC4: Routes for enrollment proof operations
Route::post('/enrollment-proofs', [EnrollmentProofController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('enrollment-proofs.store');

Route::get('/enrollment-proofs/{proof}/download', [EnrollmentProofController::class, 'download'])
    ->middleware(['auth', 'verified'])
    ->name('enrollment-proofs.download-proof');

// AC10: Status workflow routes
Route::patch('/enrollment-proofs/{enrollmentProof}/approve', [EnrollmentProofController::class, 'approve'])
    ->middleware(['auth', 'role:coordinator|admin'])
    ->name('enrollment-proofs.approve');
Route::patch('/enrollment-proofs/{enrollmentProof}/reject', [EnrollmentProofController::class, 'reject'])
    ->middleware(['auth', 'role:coordinator|admin'])
    ->name('enrollment-proofs.reject');

// Route for modifying registration
Route::post('/my-registration/modify/{registration}', [RegistrationModificationController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('registration.modify');

// Route for my registration page
Volt::route('my-registration', 'pages.my-registrations')
    ->middleware(['auth', 'verified'])
    ->name('registrations.my');

// Route for modifying registration
Volt::route('my-registration/modify', 'pages.registration-modification')
    ->middleware(['auth', 'verified'])
    ->name('registrations.modify');

// Admin dashboard - main entry point for admin interface
Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.dashboard');

// AC1: Admin approvals queue - dedicated page for pending validations
Route::get('/admin/approvals', [AdminRegistrationController::class, 'approvals'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.approvals');

// Admin dashboard API routes for progressive loading
Route::prefix('admin/dashboard')
    ->middleware(['auth', 'role:admin'])
    ->name('admin.dashboard.')
    ->group(function () {
        Route::get('/metrics/non-critical', [AdminDashboardController::class, 'getNonCriticalMetrics'])->name('non-critical-metrics');
        Route::post('/refresh', [AdminDashboardController::class, 'refreshMetrics'])->name('refresh-metrics');
    });

// Admin routes for registration management
Route::prefix('admin/registrations')
    ->middleware(['auth', 'role:admin'])
    ->name('admin.registrations.')
    ->group(function () {
        Route::get('/', [AdminRegistrationController::class, 'index'])->name('index');
        Route::get('/{registration}', [AdminRegistrationController::class, 'show'])->name('show');
        Route::get('/{registration}/download-proof', [AdminRegistrationController::class, 'downloadProof'])->name('download-proof');
        Route::patch('/{registration}/update-status', [AdminRegistrationController::class, 'updateStatus'])->name('update-status');

        // Enrollment proof management integrated into registrations
        Route::get('/{registration}/enrollment-proof/download', [AdminRegistrationController::class, 'downloadEnrollmentProof'])->name('download-enrollment-proof');
        Route::patch('/{registration}/enrollment-proof/approve', [AdminRegistrationController::class, 'approveEnrollmentProof'])->name('approve-enrollment-proof');
        Route::patch('/{registration}/enrollment-proof/reject', [AdminRegistrationController::class, 'rejectEnrollmentProof'])->name('reject-enrollment-proof');
    });

// Admin routes for enrollment proof management (integrated into registrations)
// Removed separate interface - now integrated in admin/registrations

// Admin routes for reports (AC20)
Route::prefix('admin/reports')
    ->middleware(['auth', 'role:admin'])
    ->name('admin.reports.')
    ->group(function () {
        Route::get('/', [ReportsController::class, 'index'])->name('index');
        Route::get('/enrollment-proofs', [ReportsController::class, 'enrollmentProofs'])->name('enrollment-proofs');
        Route::get('/payments', [ReportsController::class, 'payments'])->name('payments');
        Route::get('/auto-approved', [ReportsController::class, 'autoApproved'])->name('auto-approved');
    });

require __DIR__.'/auth.php';
