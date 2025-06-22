<x-mail::message>
# {{ __('Registration Modified - 8th BCSMIF') }}

## {{ __('Modification Notification') }}

{{ __('The participant') }} **{{ $registration->full_name }}** ({{ $registration->user->email }}) {{ __('from registration') }} **#{{ $registration->id }}** {{ __('has modified their event selection') }}.

## {{ __('Registration Details') }}

**{{ __('Participant') }}:** {{ $registration->full_name }}  
**{{ __('Email') }}:** {{ $registration->user->email }}  
**{{ __('Document') }}:** {{ $registration->cpf ?: $registration->passport_number }} ({{ $registration->document_country_origin }})  
**{{ __('Modification Date') }}:** {{ now()->format('d/m/Y H:i') }}

## {{ __('Updated Event Selection') }}

@foreach($registration->events as $event)
- **{{ $event->name }}**  
  {{ __('Price at registration') }}: R$ {{ number_format((float) $event->pivot->price_at_registration, 2, ',', '.') }}
@endforeach

## {{ __('Financial Information') }}

@if($registration->events->isNotEmpty())
**{{ __('New Total Amount') }}:** R$ {{ number_format($registration->events->sum('pivot.price_at_registration'), 2, ',', '.') }}  
@endif

@php
    $totalPaid = $registration->payments()->where('status', 'confirmed')->sum('amount');
    $pendingAmount = $registration->payments()->where('status', 'pending')->sum('amount');
@endphp

@if($totalPaid > 0)
**{{ __('Amount Already Paid') }}:** R$ {{ number_format($totalPaid, 2, ',', '.') }}
@endif

@if($pendingAmount > 0)
**{{ __('Amount Due') }}:** R$ {{ number_format($pendingAmount, 2, ',', '.') }}

## {{ __('Pending Payment') }}

{{ __('There is a pending payment for the additional amount due from the modification. The participant should complete payment to finalize the registration update.') }}
@endif

**{{ __('Payment Status') }}:** {{ ucfirst(str_replace('_', ' ', $registration->payment_status)) }}

## {{ __('Action Required') }}

{{ __('To review the modified registration and manage payment status, access the administrative panel') }}:

<x-mail::button :url="config('app.url') . '/admin/registrations/' . $registration->id">
{{ __('View Registration in Admin Panel') }}
</x-mail::button>

---

**{{ __('Registration ID') }}:** #{{ $registration->id }}  
**{{ __('System') }}:** {{ config('app.name') }}
</x-mail::message>