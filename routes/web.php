<?php

use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');
Route::view('workshops', 'workshops')->name('workshops');
Route::view('fees', 'fees')->name('fees');
Route::view('payment-info', 'payment-info')->name('payment-info');


Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Route for the event registration form
Volt::route('register-event', 'registration-form')
    ->middleware(['auth', 'verified'])
    ->name('register-event');

// Route for storing a new event registration
Route::post('/event-registrations', [RegistrationController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('event-registrations.store');

// Route for uploading payment proof
Route::post('/event-registrations/{registration}/upload-proof', [RegistrationController::class, 'uploadProof'])
    ->middleware(['auth', 'verified'])
    ->name('event-registrations.upload-proof');

// Route for my registrations page
Volt::route('my-registrations', 'pages.my-registrations')
    ->middleware(['auth', 'verified'])
    ->name('registrations.my');

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
