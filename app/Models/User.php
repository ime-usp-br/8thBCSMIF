<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
     * Get the registrations for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Registration, $this>
     */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get the main registration for the user (first/primary registration).
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<\App\Models\Registration, $this>
     */
    public function registration(): HasOne
    {
        return $this->hasOne(Registration::class)->oldest();
    }

    /**
     * Get the payments for the user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\App\Models\Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all event codes that are paid and therefore immutable (non-refundable).
     * Once an event is paid for, it cannot be removed or refunded.
     *
     * @return list<string>
     */
    public function getImmutableEventCodes(): array
    {
        $eventCodes = $this->payments()
            ->where('payment_status', 'like', 'paid_%')
            ->with('events')
            ->get()
            ->flatMap(fn ($payment) => $payment->getImmutableEventCodes())
            ->unique()
            ->values()
            ->toArray();

        /** @var list<string> */
        return array_values($eventCodes);
    }

    /**
     * Check if a specific event is paid and therefore immutable.
     */
    public function isEventImmutable(string $eventCode): bool
    {
        return in_array($eventCode, $this->getImmutableEventCodes());
    }

    /**
     * Get all paid payments (cannot be modified or refunded).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment>
     */
    public function getImmutablePayments()
    {
        return $this->payments()
            ->where('payment_status', 'like', 'paid_%')
            ->with('events')
            ->get();
    }
}
