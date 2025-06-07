<?php

use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('workshops', 'workshops')->name('workshops');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Route for storing a new event registration
Route::post('/event-registrations', [RegistrationController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('event-registrations.store');

// Route for uploading payment proof
Route::post('/event-registrations/{registration}/upload-proof', [RegistrationController::class, 'uploadProof'])
    ->middleware(['auth', 'verified'])
    ->name('event-registrations.upload-proof');

require __DIR__.'/auth.php';
