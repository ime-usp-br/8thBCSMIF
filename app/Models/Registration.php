<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
 * @property string $calculated_fee // Cast to decimal:2, typically string representation
 * @property string $payment_status
 * @property string|null $payment_proof_path
 * @property Carbon|null $payment_uploaded_at
 * @property Carbon|null $invoice_sent_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Event> $events
 * @property-read int|null $events_count
 */
class Registration extends Model
{
    /** @use HasFactory<\Database\Factories\RegistrationFactory> */
    use HasFactory;

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
        'calculated_fee',
        'payment_status',
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
            'date_of_birth' => 'date',
            'passport_expiry_date' => 'date',
            'arrival_date' => 'date',
            'departure_date' => 'date',
            'payment_uploaded_at' => 'datetime',
            'invoice_sent_at' => 'datetime',
            'is_abe_member' => 'boolean',
            'needs_transport_from_gru' => 'boolean',
            'needs_transport_from_usp' => 'boolean',
            'requires_visa_letter' => 'boolean',
            'sou_da_usp' => 'boolean',
            'calculated_fee' => 'decimal:2',
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
}
