<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
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

    public function updateStatus(Request $request, Registration $registration): RedirectResponse
    {
        /** @var array{payment_status: string} $validated */
        $validated = $request->validate([
            'payment_status' => [
                'required',
                Rule::in([
                    'pending_payment',
                    'pending_br_proof_approval',
                    'paid_br',
                    'invoice_sent_int',
                    'paid_int',
                    'free',
                    'cancelled',
                ]),
            ],
        ]);

        $registration->update([
            'payment_status' => $validated['payment_status'],
        ]);

        return redirect()->route('admin.registrations.show', $registration)
            ->with('success', __('Payment status updated successfully.'));
    }
}
