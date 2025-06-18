<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Represents a payment made by a user for one or more events.
 *
 * This model allows tracking multiple payments per user, enabling
 * additional event registrations after initial payment completion.
 *
 * @property int $id
 * @property int $user_id
 * @property string $payment_reference Unique reference for this payment
 * @property string $payment_method Method used (bank_transfer, international_invoice, etc.)
 * @property string $payment_status Status (pending_payment, paid_br, paid_international, etc.)
 * @property float $total_amount Total amount for this payment
 * @property string|null $payment_proof_path Path to uploaded payment proof
 * @property \Illuminate\Support\Carbon|null $payment_uploaded_at When proof was uploaded
 * @property \Illuminate\Support\Carbon|null $invoice_sent_at When invoice was sent (for internationals)
 * @property string|null $notes Administrative notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 */
class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'payment_reference',
        'payment_method',
        'payment_status',
        'total_amount',
        'payment_proof_path',
        'payment_uploaded_at',
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
            'total_amount' => 'decimal:2',
            'payment_uploaded_at' => 'datetime',
            'invoice_sent_at' => 'datetime',
        ];
    }

    /**
     * Get the user that this payment belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The events that this payment covers.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Event, $this>
     */
    public function events(): BelongsToMany
    {
        return $this->belongsToMany(
            Event::class,
            'event_payment',
            'payment_id',
            'event_code',
            'id',
            'code'
        )
            ->withPivot('individual_price', 'registration_id')
            ->withTimestamps();
    }

    /**
     * Generate a unique payment reference.
     */
    public static function generatePaymentReference(): string
    {
        return 'PAY-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6));
    }

    /**
     * Check if this payment is completed (paid).
     */
    public function isPaid(): bool
    {
        return in_array($this->payment_status, ['paid_br', 'paid_international']);
    }

    /**
     * Check if this payment is pending.
     */
    public function isPending(): bool
    {
        return $this->payment_status === 'pending_payment';
    }

    /**
     * Check if this payment makes associated events immutable (non-refundable).
     * Once paid, events cannot be removed or refunded.
     */
    public function areEventsImmutable(): bool
    {
        return $this->isPaid();
    }

    /**
     * Get event codes that are paid and therefore immutable.
     *
     * @return list<string>
     */
    public function getImmutableEventCodes(): array
    {
        if (! $this->isPaid()) {
            return [];
        }

        /** @var list<string> */
        return array_values($this->events->pluck('code')->toArray());
    }
}
