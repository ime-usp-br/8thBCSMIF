<?php

use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;
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

// Admin routes for registration management
Route::prefix('admin/registrations')
    ->middleware(['auth', 'role:admin'])
    ->name('admin.registrations.')
    ->group(function () {
        Route::get('/', [AdminRegistrationController::class, 'index'])->name('index');
        Route::get('/{registration}', [AdminRegistrationController::class, 'show'])->name('show');
        Route::get('/{registration}/download-proof', [AdminRegistrationController::class, 'downloadProof'])->name('download-proof');
        Route::patch('/{registration}/update-status', [AdminRegistrationController::class, 'updateStatus'])->name('update-status');
    });

require __DIR__.'/auth.php';
