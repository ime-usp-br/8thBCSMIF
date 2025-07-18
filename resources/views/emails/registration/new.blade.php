<x-mail::message>
# {{ __('Registration Confirmation - 8th BCSMIF') }}

{{ __('Hello') }} {{ $registration->full_name }},

{{ __('Thank you for registering for the 8th Brazilian Congress on Statistical Modeling in Insurance and Finance (8th BCSMIF)!') }}

## {{ __('Registration Summary') }}

**{{ __('Selected Events') }}:**
@foreach($registration->events as $event)
- {{ $event->name }}: R$ {{ number_format((float) $event->pivot->price_at_registration, 2, ',', '.') }}
@endforeach

**{{ __('Total Amount') }}:** R$ {{ number_format($registration->events->sum('pivot.price_at_registration'), 2, ',', '.') }}

@if($registration->position === 'undergraduate_student' && $registration->events->sum('pivot.price_at_registration') == 0)
**{{ __('Enrollment Information') }}:**
{{ __('As an undergraduate student, your tuition fee is zero. Instead of proof of payment, we ask that you submit a valid proof of enrollment.') }}
@elseif($registration->events->sum('pivot.price_at_registration') > 0)
**{{ __('Payment Status') }}:** {{ ucfirst(str_replace('_', ' ', $registration->payment_status)) }}

@if($registration->document_country_origin === 'Brazil')
## {{ __('Payment Instructions') }}

{{ __('Please make payment via bank transfer to the details below:') }}

**{{ __('Bank Transfer Information:') }}**
- **{{ __('Bank:') }}** Santander
- **{{ __('Agency:') }}** 0658
- **{{ __('Account:') }}** 13006798-9

- **{{ __('Beneficiary') }}:** Associação Brasileira de Estatística
- **{{ __('CNPJ') }}:** 56.572.456/0001-80

**{{ __('how to send the payment proof') }}:**
{{ __('After making payment, access your account in the system and upload the payment proof. Your status will be updated once confirmation is processed.') }}
@else
**{{ __('Invoice Information') }}:**
{{ __('Thank you for your registration. Your invoice for international payment will be sent shortly from assoc.bras.estatistica@gmail.com. Please check your inbox and spam folder.') }}
@endif
@else
**{{ __('Payment Status') }}:** {{ __('Fee exempt') }}
@endif

## {{ __('Next Steps') }}

{{ __('Keep this email for your records. We will contact you soon with more information about the event.') }}

{{ __('Best regards') }},<br>
{{ __('Organization of') }} {{ config('app.name') }}
</x-mail::message>
