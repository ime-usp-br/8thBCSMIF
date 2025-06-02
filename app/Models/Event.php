<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Represents an event or workshop in the 8th BCSMIF application.
 *
 * @property int $id
 * @property string $code Unique short code for the event (e.g., BCSMIF2025).
 * @property string $name Full name of the event or workshop.
 * @property string|null $description Detailed description of the event.
 * @property \Illuminate\Support\Carbon $start_date Event start date.
 * @property \Illuminate\Support\Carbon $end_date Event end date.
 * @property string $location Location where the event will take place.
 * @property \Illuminate\Support\Carbon|null $registration_deadline_early Deadline for early bird registration discount.
 * @property \Illuminate\Support\Carbon|null $registration_deadline_late Final deadline for registration.
 * @property bool $is_main_conference Indicates if this is the main conference event.
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Registration> $registrations
 * @property-read int|null $registrations_count
 */
class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'start_date',
        'end_date',
        'location',
        'registration_deadline_early',
        'registration_deadline_late',
        'is_main_conference',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_deadline_early' => 'date',
            'registration_deadline_late' => 'date',
            'is_main_conference' => 'boolean',
        ];
    }

    /**
     * The registrations that belong to the event.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<\App\Models\Registration, $this, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function registrations(): BelongsToMany
    {
        return $this->belongsToMany(
            Registration::class,
            'event_registration', // Pivot table name
            'event_code',         // Foreign key on pivot table for Event model
            'registration_id',    // Foreign key on pivot table for Registration model
            'code',               // Parent key on Event model (it's 'code')
            'id'                  // Related key on Registration model (default: id)
        )
            ->withPivot('price_at_registration')
            ->withTimestamps();
    }
}
