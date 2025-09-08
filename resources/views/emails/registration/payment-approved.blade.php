<x-mail::message>
# {{ $approvalType === 'exemption' ? __('Fee Exemption Approved - 8th BCSMIF') : __('Payment Approved - 8th BCSMIF') }}

{{ __('Hello') }} {{ $registration->full_name }},

@if($approvalType === 'exemption')
{{ __('We are pleased to inform you that your fee exemption request for the 8th Brazilian Congress on Statistical Modeling in Insurance and Finance (8th BCSMIF) has been approved.') }}

## 🎉 {{ __('Fee Exemption Confirmed') }}

{{ __('Your registration has been confirmed as fee-exempt. Your participation is now secured for the conference without payment requirements.') }}

@else
{{ __('We are pleased to inform you that your payment for the 8th Brazilian Congress on Statistical Modeling in Insurance and Finance (8th BCSMIF) has been approved.') }}

## 🎉 {{ __('Payment Confirmed!') }}

{{ __('Great news! Your payment has been confirmed and your registration is now complete. You will receive additional information about the event soon.') }}
@endif

## {{ __('Registration Summary') }}

**{{ __('Registration ID') }}:** #{{ $registration->id }}  
**{{ __('Registration Status') }}:** {{ __('Approved') }}
@if($approvalType !== 'exemption')
**{{ __('Total Amount') }}:** R$ {{ number_format($registration->calculateCorrectTotalFee(), 2, ',', '.') }}
@endif

@if($registration->events->count() > 0)
**{{ __('Selected Events') }}:**
@foreach($registration->events as $event)
- {{ $event->name }}@if($approvalType !== 'exemption'): R$ {{ number_format((float) $event->pivot->price_at_registration, 2, ',', '.') }}@endif
@endforeach
@endif

## {{ __('Next Steps') }}

{{ __('Keep this email for your records. We will contact you soon with more information about the event, including venue details, schedule, and additional instructions.') }}

@if($approvalType === 'exemption')
{{ __('Please note that this exemption was granted by our administrative team based on your specific circumstances. If you have any questions about this exemption, please contact us.') }}
@endif

@if(Route::has('my-registration'))
<x-mail::button :url="route('my-registration')" color="primary">
{{ __('View My Registration') }}
</x-mail::button>
@endif

{{ __('If you have any questions or concerns, please do not hesitate to contact us.') }}

{{ __('Best regards') }},<br>
{{ __('Organization of') }} {{ config('app.name') }}
</x-mail::message>