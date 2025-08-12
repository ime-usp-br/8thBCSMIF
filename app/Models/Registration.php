<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon; // For date type hints in PHPDoc

/**
 * App\Models\Registration
 *
 * @property int $id
 * @property int $user_id
 * @property string $full_name
 * @property string|null $nationality
 * @property Carbon|null $date_of_birth
 * @property string|null $gender
 * @property string|null $document_country_origin
 * @property string|null $cpf
 * @property string|null $rg_number
 * @property string|null $passport_number
 * @property Carbon|null $passport_expiry_date
 * @property string $email
 * @property string|null $phone_number
 * @property string|null $address_street
 * @property string|null $address_city
 * @property string|null $address_state_province
 * @property string|null $address_country
 * @property string|null $address_postal_code
 * @property string|null $affiliation
 * @property string|null $position
 * @property bool|null $is_abe_member
 * @property Carbon|null $arrival_date
 * @property Carbon|null $departure_date
 * @property string|null $participation_format
 * @property bool $needs_transport_from_gru
 * @property bool $needs_transport_from_usp
 * @property string|null $dietary_restrictions
 * @property string|null $other_dietary_restrictions
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_relationship
 * @property string|null $emergency_contact_phone
 * @property bool $requires_visa_letter
 * @property string $registration_category_snapshot
 * @property string $status
 * @property Carbon|null $invoice_sent_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int|null $events_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EnrollmentProof> $enrollmentProofs
 * @property-read int|null $enrollment_proofs_count
 * @property-read \App\Models\EnrollmentProof|null $enrollmentProof
 */
class Registration extends Model
{
    /** @use HasFactory<\Database\Factories\RegistrationFactory> */
    use HasFactory;

    const STATUS_PENDING = 'pending';

    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'full_name',
        'nationality',
        'date_of_birth',
        'gender',
        'document_country_origin',
        'cpf',
        'rg_number',
        'passport_number',
        'passport_expiry_date',
        'email',
        'phone_number',
        'address_street',
        'address_city',
        'address_state_province',
        'address_country',
        'address_postal_code',
        'affiliation',
        'position',
        'is_abe_member',
        'arrival_date',
        'departure_date',
        'participation_format',
        'needs_transport_from_gru',
        'needs_transport_from_usp',
        'dietary_restrictions',
        'other_dietary_restrictions',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'requires_visa_letter',
        'registration_category_snapshot',
        'status',
        'payment_status',
        'invoice_sent_at',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'passport_expiry_date' => 'date',
            'arrival_date' => 'date',
            'departure_date' => 'date',
            'invoice_sent_at' => 'datetime',
            'is_abe_member' => 'boolean',
            'needs_transport_from_gru' => 'boolean',
            'needs_transport_from_usp' => 'boolean',
            'requires_visa_letter' => 'boolean',
        ];
    }

    /**
     * Get the user that this registration belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The events that this registration is associated with.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Event, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(
            Event::class,
            'event_registration', // Pivot table name
            'registration_id',    // Foreign key on pivot table for Registration model
            'event_code',         // Foreign key on pivot table for Event model
            'id',                 // Parent key on Registration model (default: id)
            'code'                // Related key on Event model (default: id, but here it's 'code')
        )
            ->withPivot('price_at_registration')
            ->withTimestamps();
    }

    /**
     * The payments that belong to this registration.
     *
     * Note: The 'status' column on this model serves as a consolidated status
     * reflecting the overall payment state across all associated payments. This allows
     * for quick queries and reporting while maintaining detailed payment records
     * in the related Payment models.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The enrollment proofs that belong to this registration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\EnrollmentProof, $this>
     */
    public function enrollmentProofs(): HasMany
    {
        return $this->hasMany(EnrollmentProof::class);
    }

    /**
     * Get the latest enrollment proof for this registration.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\EnrollmentProof, $this>
     */
    public function enrollmentProof(): HasOne
    {
        return $this->hasOne(EnrollmentProof::class)->latestOfMany();
    }

    /**
     * Calculate the correct total fee for this registration using FeeCalculationService.
     * This method applies retroactive discounts and ensures consistency across all pages.
     *
     * @return float The calculated total fee with any applicable discounts
     */
    public function calculateCorrectTotalFee(): float
    {
        $fallbackSum = $this->events->sum('pivot.price_at_registration');
        $fallbackFee = is_numeric($fallbackSum) ? (float) $fallbackSum : 0.0;

        if (! $this->registration_category_snapshot) {
            return $fallbackFee;
        }

        /** @var list<string> $eventCodes */
        $eventCodes = array_values($this->events->pluck('code')->toArray());
        $feeService = app(\App\Services\FeeCalculationService::class);

        $feeCalculation = $feeService->calculateFees(
            $this->registration_category_snapshot,
            $eventCodes,
            $this->created_at ?? now(),
            $this->participation_format === 'online' ? 'online' : 'in-person',
            $this
        );

        $newTotalFee = $feeCalculation['new_total_fee'] ?? null;

        return is_numeric($newTotalFee) ? (float) $newTotalFee : $fallbackFee;
    }

    /**
     * Get all valid payment status values.
     *
     * @return array<string>
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING,
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }

    /**
     * Check if the payment status is pending.
     */
    public function isStatusPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the payment status is pending approval.
     */
    public function isStatusPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Check if the payment status is approved.
     */
    public function isStatusApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the payment status is rejected.
     */
    public function isStatusRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Calculate the correct status based on registration category and related models.
     *
     * Business Rules:
     * - undergrad_student: Status reflects enrollment proof status only
     * - grad_student: Status requires both payment and enrollment proof to be approved
     * - Other categories: Status reflects most recent payment status only
     *
     * @return string The calculated status
     */
    public function calculateStatusFromRelatedModels(): string
    {
        $category = $this->registration_category_snapshot;

        if ($category === 'undergrad_student') {
            // For undergrad students, status depends only on enrollment proof
            $latestEnrollmentProof = $this->enrollmentProof;

            if (! $latestEnrollmentProof) {
                return self::STATUS_PENDING;
            }

            return $latestEnrollmentProof->status;
        }

        if ($category === 'grad_student') {
            // For grad students, both payment and enrollment proof are required
            $latestPayment = $this->payments()->latest()->first();
            $latestEnrollmentProof = $this->enrollmentProof;

            // If no payment or enrollment proof exists, status is pending
            if (! $latestPayment || ! $latestEnrollmentProof) {
                return self::STATUS_PENDING;
            }

            // Both must be approved for registration to be approved
            if ($latestPayment->status === self::STATUS_APPROVED &&
                $latestEnrollmentProof->status === self::STATUS_APPROVED) {
                return self::STATUS_APPROVED;
            }

            // If either is rejected, registration is rejected
            if ($latestPayment->status === self::STATUS_REJECTED ||
                $latestEnrollmentProof->status === self::STATUS_REJECTED) {
                return self::STATUS_REJECTED;
            }

            // If any is pending approval, registration is pending approval
            if ($latestPayment->status === self::STATUS_PENDING_APPROVAL ||
                $latestEnrollmentProof->status === self::STATUS_PENDING_APPROVAL) {
                return self::STATUS_PENDING_APPROVAL;
            }

            // Default to pending if any component is pending
            return self::STATUS_PENDING;
        }

        // For other categories (professor, professional, etc.), status mirrors most recent payment
        $latestPayment = $this->payments()->latest()->first();

        if (! $latestPayment) {
            return self::STATUS_PENDING;
        }

        return $latestPayment->status;
    }

    /**
     * Update the status based on related models.
     * This method should be called by observers when related models change.
     *
     * @return bool True if status was actually changed, false otherwise
     */
    public function updateStatusFromRelatedModels(): bool
    {
        $newStatus = $this->calculateStatusFromRelatedModels();

        if ($this->status !== $newStatus) {
            // Use updateQuietly to avoid triggering observers and prevent infinite loops
            $this->updateQuietly(['status' => $newStatus]);

            return true;
        }

        return false;
    }
}
