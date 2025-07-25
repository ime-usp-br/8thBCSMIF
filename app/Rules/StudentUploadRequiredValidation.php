<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class StudentUploadRequiredValidation implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * The user being validated.
     */
    protected ?User $user;

    /**
     * Create a new rule instance.
     */
    public function __construct(?User $user = null)
    {
        $this->user = $user;
    }

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * Validates that students (both undergraduate and graduate) have uploaded
     * required enrollment proof documents. This rule enforces mandatory upload
     * for all student categories in Phase 5.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Get the registration category from form data
        $registrationCategory = $this->data['registration_category_snapshot'] ?? null;

        // Check if this is a student category
        if (! in_array($registrationCategory, ['undergrad_student', 'grad_student'])) {
            return; // Rule only applies to students
        }

        // If we have a user context, check their validation status first
        if ($this->user) {
            // Check if user has any pending or approved enrollment proof
            $hasValidEnrollmentProof = $this->user->registration()
                ->whereHas('enrollmentProof', function ($query) {
                    $query->whereIn('status', ['pending_approval', 'approved']);
                })->exists();

            if (! $hasValidEnrollmentProof) {
                $fail(__('Students must upload enrollment proof documents before completing registration.'));
            }
            return; // If user context exists, use that for validation
        }

        // For new registrations without user context, check if a valid enrollment document is uploaded
        // The $value should be the uploaded file or enrollment proof
        if (empty($value)) {
            $fail(__('Students must upload enrollment proof documents. Both undergraduate and graduate students are required to provide valid enrollment documentation.'));
        }
    }
}
