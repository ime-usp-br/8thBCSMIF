<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationController extends Controller
{
    public function index(): View
    {
        return view('admin.registrations.index');
    }

    public function show(Registration $registration): View
    {
        $registration->load(['user', 'events']);

        return view('admin.registrations.show', compact('registration'));
    }

    public function downloadProof(Registration $registration): BinaryFileResponse|StreamedResponse|Response
    {
        if (! $registration->payment_proof_path) {
            abort(404, __('Payment proof not found'));
        }

        if (! Storage::disk('private')->exists($registration->payment_proof_path)) {
            abort(404, __('Payment proof file not found'));
        }

        return Storage::disk('private')->download($registration->payment_proof_path);
    }
}
