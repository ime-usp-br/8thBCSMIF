<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentProof;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentProofController extends Controller
{
    public function index(): View
    {
        // Check authorization
        Gate::authorize('manageEnrollmentProofs');

        return view('admin.enrollment-proofs.index');
    }

    public function show(EnrollmentProof $enrollmentProof): View
    {
        // Check authorization
        Gate::authorize('manageEnrollmentProofs');

        // Load relationships
        $enrollmentProof->load(['registration.user', 'registration.events', 'approvedBy']);

        return view('admin.enrollment-proofs.show', compact('enrollmentProof'));
    }

    public function download(EnrollmentProof $enrollmentProof): BinaryFileResponse|StreamedResponse
    {
        // Check authorization
        Gate::authorize('manageEnrollmentProofs');

        // Validate that enrollment proof has a file
        if (! $enrollmentProof->file_path) {
            abort(404, __('Enrollment proof file not found.'));
        }

        // Check if file exists in storage
        if (! Storage::disk('private')->exists($enrollmentProof->file_path)) {
            abort(404, __('Enrollment proof file not found in storage.'));
        }

        // Get original filename for download
        $originalFilename = $enrollmentProof->original_filename ?: basename($enrollmentProof->file_path);

        // Generate a user-friendly filename for admin download
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $friendlyFilename = 'enrollment_proof_reg_'.$enrollmentProof->registration_id.'_'.time().'.'.($extension ?: 'pdf');

        return Storage::disk('private')->download(
            $enrollmentProof->file_path,
            $friendlyFilename
        );
    }
}
