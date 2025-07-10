<?php

namespace App\Http\Controllers;

use App\Mail\ProofUploadedNotification;
use App\Models\EnrollmentProof;
use App\Models\Registration;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentProofController extends Controller
{
    /**
     * Upload enrollment proof for a specific registration.
     */
    public function uploadProof(Request $request, Registration $registration): RedirectResponse
    {
        // Validate that user owns this registration
        Gate::authorize('uploadProof', $registration);

        // Validate the uploaded file
        $request->validate([
            'enrollment_proof' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:10240', // 10MB max
            ],
        ], [
            'enrollment_proof.required' => __('Enrollment proof file is required. Please contact the organization for assistance if you are unable to upload.'),
            'enrollment_proof.file' => __('Enrollment proof must be a valid file. Please contact the organization for assistance if you continue to experience issues.'),
            'enrollment_proof.mimes' => __('Enrollment proof must be a JPG, JPEG, PNG, or PDF file. Please contact the organization for assistance if your file format is not supported.'),
            'enrollment_proof.max' => __('Enrollment proof file size must not exceed 10MB. Please contact the organization for assistance if you need to upload a larger file.'),
        ]);

        // Check if an enrollment proof already exists for this registration
        $existingProof = $registration->enrollmentProof;
        if ($existingProof && $existingProof->status === 'approved') {
            return redirect()->back()->with('error', __('Enrollment proof has already been approved and cannot be replaced.'));
        }

        try {
            // Store the uploaded file with sanitized filename
            $uploadedFile = $request->file('enrollment_proof');
            if (! $uploadedFile instanceof \Illuminate\Http\UploadedFile) {
                throw new \RuntimeException('No file was uploaded.');
            }

            // Generate sanitized and unique filename
            $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $uploadedFile->getClientOriginalExtension();
            $sanitizedName = Str::slug($originalName);
            $filename = time().'_enrollment_'.$registration->id.'_'.$sanitizedName.'.'.$extension;

            $path = $uploadedFile->storeAs("enrollment-proofs/{$registration->id}", $filename, 'private');

            // Create or update enrollment proof
            $enrollmentProof = $registration->enrollmentProof ?: new EnrollmentProof(['registration_id' => $registration->id]);

            $enrollmentProof->fill([
                'file_path' => $path,
                'original_filename' => $uploadedFile->getClientOriginalName(),
                'uploaded_at' => Carbon::now(),
                'status' => 'pending_approval',
                'approved_at' => null,
                'approved_by' => null,
                'rejection_reason' => null,
            ]);

            $enrollmentProof->save();

            $user = $request->user();
            Log::info(__('Enrollment proof uploaded successfully'), [
                'registration_id' => $registration->id,
                'enrollment_proof_id' => $enrollmentProof->id,
                'file_path' => $path,
                'user_id' => $user?->id,
            ]);

            // Dispatch ProofUploadedNotification to coordinator for enrollment proof
            $coordinatorEmail = ProofUploadedNotification::getCoordinatorEmail();
            if ($coordinatorEmail) {
                Mail::to($coordinatorEmail)->queue(new ProofUploadedNotification($registration, 'enrollment'));
                Log::info(__('Enrollment proof upload notification sent to coordinator'), [
                    'registration_id' => $registration->id,
                    'enrollment_proof_id' => $enrollmentProof->id,
                    'coordinator_email' => $coordinatorEmail,
                ]);
            }

            return redirect()->back()->with('success', __('Enrollment proof uploaded successfully. The coordinator will review your submission.'));

        } catch (\Exception $e) {
            $user = $request->user();
            Log::error(__('Failed to upload enrollment proof'), [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
            ]);

            return redirect()->back()->with('error', __('Failed to upload enrollment proof. Please contact the organization for assistance.'));
        }
    }

    /**
     * Download enrollment proof for a specific registration.
     * Only the owner of the registration can download their enrollment proof.
     */
    public function downloadProof(Registration $registration): BinaryFileResponse|StreamedResponse
    {
        // Validate that user owns this registration
        Gate::authorize('uploadProof', $registration);

        // Get the enrollment proof for this registration
        $enrollmentProof = $registration->enrollmentProof;

        // Validate that enrollment proof exists and has a file
        if (! $enrollmentProof || ! $enrollmentProof->file_path) {
            abort(404, __('Enrollment proof not found.'));
        }

        // Check if file exists in storage
        if (! Storage::disk('private')->exists($enrollmentProof->file_path)) {
            abort(404, __('Enrollment proof file not found in storage.'));
        }

        $user = request()->user();
        Log::info(__('Enrollment proof downloaded'), [
            'registration_id' => $registration->id,
            'enrollment_proof_id' => $enrollmentProof->id,
            'file_path' => $enrollmentProof->file_path,
            'user_id' => $user?->id,
        ]);

        // Get original filename for download
        $originalFilename = $enrollmentProof->original_filename ?: basename($enrollmentProof->file_path);

        // Generate a user-friendly filename
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $friendlyFilename = 'enrollment_proof_'.$registration->id.'.'.($extension ?: 'pdf');

        return Storage::disk('private')->download(
            $enrollmentProof->file_path,
            $friendlyFilename
        );
    }

    /**
     * Store enrollment proof (AC4 route - POST /enrollment-proofs).
     * Expects registration_id in request body.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate request data
        $request->validate([
            'registration_id' => ['required', 'integer', 'exists:registrations,id'],
            'enrollment_proof' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:10240', // 10MB max
            ],
        ]);

        // Get the registration
        $registration = Registration::findOrFail($request->registration_id);
        assert($registration instanceof Registration);

        // Validate that user owns this registration
        Gate::authorize('uploadProof', $registration);

        // Check if an enrollment proof already exists for this registration
        $existingProof = $registration->enrollmentProof;
        if ($existingProof && $existingProof->status === 'approved') {
            return redirect()->back()->with('error', __('Enrollment proof has already been approved and cannot be replaced.'));
        }

        try {
            // Store the uploaded file with sanitized filename
            $uploadedFile = $request->file('enrollment_proof');
            if (! $uploadedFile instanceof \Illuminate\Http\UploadedFile) {
                throw new \RuntimeException('No file was uploaded.');
            }

            // Generate sanitized and unique filename
            $originalName = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $uploadedFile->getClientOriginalExtension();
            $sanitizedName = Str::slug($originalName);
            $filename = time().'_enrollment_'.$registration->id.'_'.$sanitizedName.'.'.$extension;

            $path = $uploadedFile->storeAs("enrollment-proofs/{$registration->id}", $filename, 'private');

            // Create or update enrollment proof
            $enrollmentProof = $registration->enrollmentProof ?: new EnrollmentProof(['registration_id' => $registration->id]);

            $enrollmentProof->fill([
                'file_path' => $path,
                'original_filename' => $uploadedFile->getClientOriginalName(),
                'uploaded_at' => Carbon::now(),
                'status' => 'pending_approval',
                'approved_at' => null,
                'approved_by' => null,
                'rejection_reason' => null,
            ]);

            $enrollmentProof->save();

            $user = $request->user();
            Log::info(__('Enrollment proof uploaded successfully via store method'), [
                'registration_id' => $registration->id,
                'enrollment_proof_id' => $enrollmentProof->id,
                'file_path' => $path,
                'user_id' => $user?->id,
            ]);

            // Dispatch ProofUploadedNotification to coordinator for enrollment proof
            $coordinatorEmail = ProofUploadedNotification::getCoordinatorEmail();
            if ($coordinatorEmail) {
                Mail::to($coordinatorEmail)->queue(new ProofUploadedNotification($registration, 'enrollment'));
                Log::info(__('Enrollment proof upload notification sent to coordinator via store method'), [
                    'registration_id' => $registration->id,
                    'enrollment_proof_id' => $enrollmentProof->id,
                    'coordinator_email' => $coordinatorEmail,
                ]);
            }

            return redirect()->back()->with('success', __('Enrollment proof uploaded successfully. The coordinator will review your submission.'));

        } catch (\Exception $e) {
            $user = $request->user();
            Log::error(__('Failed to upload enrollment proof via store method'), [
                'registration_id' => $registration->id,
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
            ]);

            return redirect()->back()->with('error', __('Failed to upload enrollment proof. Please contact the organization for assistance.'));
        }
    }

    /**
     * Download enrollment proof by proof ID (AC4 route - GET /enrollment-proofs/{proof}/download).
     */
    public function download(EnrollmentProof $proof): BinaryFileResponse|StreamedResponse
    {
        // Load the registration relationship
        $proof->load('registration');

        // Validate that user owns this registration
        Gate::authorize('uploadProof', $proof->registration);

        // Validate that enrollment proof has a file
        if (! $proof->file_path) {
            abort(404, __('Enrollment proof not found.'));
        }

        // Check if file exists in storage
        if (! Storage::disk('private')->exists($proof->file_path)) {
            abort(404, __('Enrollment proof file not found in storage.'));
        }

        $user = request()->user();
        Log::info(__('Enrollment proof downloaded via download method'), [
            'registration_id' => $proof->registration_id,
            'enrollment_proof_id' => $proof->id,
            'file_path' => $proof->file_path,
            'user_id' => $user?->id,
        ]);

        // Get original filename for download
        $originalFilename = $proof->original_filename ?: basename($proof->file_path);

        // Generate a user-friendly filename
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $friendlyFilename = 'enrollment_proof_'.$proof->registration_id.'.'.($extension ?: 'pdf');

        return Storage::disk('private')->download(
            $proof->file_path,
            $friendlyFilename
        );
    }
}
