<x-mail::message>
# {{ __('Payment Status Updated - 8th BCSMIF') }}

{{ __('Hello') }} {{ $registration->full_name }},

{{ __('We are writing to inform you that the payment status for your registration to the 8th Brazilian Congress on Statistical Modeling in Insurance and Finance (8th BCSMIF) has been updated.') }}

## {{ __('Payment Status Change') }}

**{{ __('Previous Status') }}:** {{ ucfirst(str_replace('_', ' ', $oldStatus)) }}  
**{{ __('New Status') }}:** {{ ucfirst(str_replace('_', ' ', $newStatus)) }}

@if($newStatus === 'paid_br' || $newStatus === 'paid_int')
## 🎉 {{ __('Payment Confirmed!') }}

{{ __('Great news! Your payment has been confirmed and your registration is now complete. You will receive additional information about the event soon.') }}

@elseif($newStatus === 'free')
## {{ __('Fee Exemption Confirmed') }}

{{ __('Your registration has been confirmed as fee-exempt. Your participation is now secured for the conference.') }}

@elseif($newStatus === 'cancelled')
## {{ __('Registration Cancelled') }}

{{ __('Your registration has been cancelled. If you believe this is an error, please contact us immediately.') }}

@elseif($newStatus === 'invoice_sent_int')
## {{ __('Invoice Sent') }}

{{ __('An invoice for international payment has been prepared and sent to you. Please check your email for payment instructions.') }}

@else
## {{ __('Status Update') }}

{{ __('Your payment status has been updated. If you have any questions about this change, please contact our support team.') }}
@endif

## {{ __('Registration Summary') }}

**{{ __('Registration ID') }}:** #{{ $registration->id }}  
**{{ __('Total Amount') }}:** R$ {{ number_format((float) $registration->calculated_fee, 2, ',', '.') }}

@if($registration->events->count() > 0)
**{{ __('Selected Events') }}:**
@foreach($registration->events as $event)
- {{ $event->name }}: R$ {{ number_format((float) $event->pivot->price_at_registration, 2, ',', '.') }}
@endforeach
@endif

@if($newStatus === 'paid_br' || $newStatus === 'paid_int' || $newStatus === 'free')
## {{ __('Next Steps') }}

{{ __('Keep this email for your records. We will contact you soon with more information about the event, including venue details, schedule, and additional instructions.') }}
@endif

{{ __('If you have any questions or concerns, please do not hesitate to contact us.') }}

{{ __('Best regards') }},<br>
{{ __('Organization of') }} {{ config('app.name') }}
</x-mail::message>