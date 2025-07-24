<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Uspdev\SenhaunicaSocialite\Traits\HasSenhaunica;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;
    use HasSenhaunica;

    // Student validation status constants
    const STUDENT_STATUS_NOT_REQUIRED = 'not_required';

    const STUDENT_STATUS_PENDING_VALIDATION = 'pending_validation';

    const STUDENT_STATUS_APPROVED = 'approved';

    const STUDENT_STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'codpes',
        'student_validation_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the registration for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\Registration, $this>
     */
    public function registration(): HasOne
    {
        return $this->hasOne(Registration::class);
    }

    /**
     * Get the registrations for the user (backward compatibility).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get enrollment proofs through the user's registration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough<\App\Models\EnrollmentProof, \App\Models\Registration, $this>
     */
    public function enrollmentProofs(): HasManyThrough
    {
        return $this->hasManyThrough(EnrollmentProof::class, Registration::class);
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * Get all valid student validation status values.
     *
     * @return array<string>
     */
    public static function getValidStudentStatuses(): array
    {
        return [
            self::STUDENT_STATUS_NOT_REQUIRED,
            self::STUDENT_STATUS_PENDING_VALIDATION,
            self::STUDENT_STATUS_APPROVED,
            self::STUDENT_STATUS_REJECTED,
        ];
    }

    /**
     * Check if student validation is required for this user.
     */
    public function requiresStudentValidation(): bool
    {
        return $this->student_validation_status !== self::STUDENT_STATUS_NOT_REQUIRED;
    }

    /**
     * Check if student validation is pending.
     */
    public function hasStudentValidationPending(): bool
    {
        return $this->student_validation_status === self::STUDENT_STATUS_PENDING_VALIDATION;
    }

    /**
     * Check if student validation is approved.
     */
    public function hasStudentValidationApproved(): bool
    {
        return $this->student_validation_status === self::STUDENT_STATUS_APPROVED;
    }

    /**
     * Check if student validation is rejected.
     */
    public function hasStudentValidationRejected(): bool
    {
        return $this->student_validation_status === self::STUDENT_STATUS_REJECTED;
    }

    /**
     * Set student validation status to pending.
     */
    public function setStudentValidationPending(): void
    {
        $this->update(['student_validation_status' => self::STUDENT_STATUS_PENDING_VALIDATION]);
    }

    /**
     * Approve student validation status.
     */
    public function approveStudentValidation(): void
    {
        $this->update(['student_validation_status' => self::STUDENT_STATUS_APPROVED]);
    }

    /**
     * Reject student validation status.
     */
    public function rejectStudentValidation(): void
    {
        $this->update(['student_validation_status' => self::STUDENT_STATUS_REJECTED]);
    }
}
