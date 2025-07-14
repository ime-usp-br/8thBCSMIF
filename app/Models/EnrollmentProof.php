<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\EnrollmentProof
 *
 * @property int $id
 * @property int $registration_id
 * @property string|null $file_path
 * @property string|null $original_filename
 * @property Carbon|null $uploaded_at
 * @property string $status
 * @property Carbon|null $approved_at
 * @property int|null $approved_by
 * @property string|null $rejection_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\Registration $registration
 * @property-read \App\Models\User|null $approvedBy
 */
class EnrollmentProof extends Model
{
    /** @use HasFactory<\Database\Factories\EnrollmentProofFactory> */
    use HasFactory;

    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'registration_id',
        'file_path',
        'original_filename',
        'uploaded_at',
        'status',
        'approved_at',
        'approved_by',
        'rejection_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the registration that this enrollment proof belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Registration, $this>
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    /**
     * Get the user who approved this enrollment proof.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get all valid status values.
     *
     * @return array<string>
     */
    public static function getValidStatuses(): array
    {
        return [
            self::STATUS_PENDING_APPROVAL,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ];
    }

    /**
     * Approve this enrollment proof.
     *
     * @param  \App\Models\User  $approver  The user performing the approval
     * @return bool True if successfully approved, false if already processed
     */
    public function approve(User $approver): bool
    {
        if ($this->status !== self::STATUS_PENDING_APPROVAL) {
            return false; // Can only approve pending proofs
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => null, // Clear any previous rejection
        ]);

        return true;
    }

    /**
     * Reject this enrollment proof with a reason.
     *
     * @param  \App\Models\User  $approver  The user performing the rejection
     * @param  string  $reason  The reason for rejection
     * @return bool True if successfully rejected, false if already processed
     */
    public function reject(User $approver, string $reason): bool
    {
        if ($this->status !== self::STATUS_PENDING_APPROVAL) {
            return false; // Can only reject pending proofs
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        return true;
    }

    /**
     * Check if the enrollment proof is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Check if the enrollment proof is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the enrollment proof is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
