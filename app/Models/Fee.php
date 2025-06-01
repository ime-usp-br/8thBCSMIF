<?php

namespace App\Models;

use Database\Factories\FeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a fee structure for an event based on participant category,
 * participation type, and registration period.
 *
 * @property int $id
 * @property string $event_code Code of the event this fee belongs to.
 * @property string $participant_category Category of the participant (e.g., 'undergrad_student').
 * @property string $type Type of participation (e.g., 'in-person', 'online').
 * @property string $period Registration period (e.g., 'early', 'late').
 * @property float $price The actual price for this fee combination.
 * @property bool $is_discount_for_main_event_participant Indicates if this is a discounted fee for main event attendees (for workshops).
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event The event this fee is associated with.
 *
 * @method static \Database\Factories\FeeFactory factory($count = null, $state = [])
 */
class Fee extends Model
{
    /** @use HasFactory<FeeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'event_code',
        'participant_category',
        'type',
        'period',
        'price',
        'is_discount_for_main_event_participant',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_discount_for_main_event_participant' => 'boolean',
        ];
    }

    /**
     * Get the event that this fee belongs to.
     *
     * This defines an inverse one-to-many relationship where a Fee
     * belongs to an Event. The 'event_code' on the 'fees' table
     * is the foreign key, and it references the 'code' column
     * on the 'events' table (which is the owner key).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_code', 'code');
    }
}
