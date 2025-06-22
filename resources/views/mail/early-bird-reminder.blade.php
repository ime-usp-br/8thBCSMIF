<x-mail::message>
# {{ __('Early Bird Deadline Reminder - 8th BCSMIF') }}

{{ __('Hello') }} {{ $registration->full_name }},

{{ __('This is a friendly reminder that the early bird registration deadline for the 8th Brazilian Congress on Statistical Modeling in Insurance and Finance (8th BCSMIF) is approaching soon!') }}

## {{ __('Event Information') }}

**{{ __('Event') }}:** {{ $event->name }}
**{{ __('Early Bird Deadline') }}:** {{ $event->registration_deadline_early->format('d/m/Y') }}

## {{ __('Current Registration Status') }}

**{{ __('Payment Status') }}:** {{ __('Pending') }}

{{ __('You still have pending payments for your registration. To secure the early bird discount and avoid higher fees, please complete your payment before the deadline.') }}

**{{ __('Current Registration Summary') }}:**
@foreach($registration->events as $event)
- {{ $event->name }}: R$ {{ number_format((float) $event->pivot->price_at_registration, 2, ',', '.') }}
@endforeach

**{{ __('Total Amount Due') }}:** R$ {{ number_format($registration->events->sum('pivot.price_at_registration'), 2, ',', '.') }}

@if($registration->document_country_origin === 'BR' || $registration->document_country_origin === 'Brazil')
## {{ __('Payment Instructions') }}

{{ __('Please make payment via bank transfer or PIX to the details below:') }}

**{{ __('Bank Transfer Information:') }}**
- **{{ __('Bank:') }}** Santander
- **{{ __('Agency:') }}** 0658
- **{{ __('Account:') }}** 13006798-9
- **{{ __('PIX Key:') }}** 56.572.456/0001-80
- **{{ __('Beneficiary') }}:** Associação Brasileira de Estatística
- **{{ __('CNPJ') }}:** 56.572.456/0001-80

**{{ __('how to send the payment proof') }}:**
{{ __('After making payment, access your account in the system and upload the payment proof. Your status will be updated once confirmation is processed.') }}
@else
**{{ __('Invoice Information') }}:**
{{ __('An invoice with details for international payment was sent to your email.') }}
@endif

<x-mail::button :url="route('registrations.my')">
{{ __('Access Your Registration') }}
</x-mail::button>

{{ __('Don\'t miss the early bird discount! Complete your payment soon.') }}

{{ __('Best regards') }},<br>
{{ __('Organization of') }} {{ config('app.name') }}
</x-mail::message>
