<x-mail::message>
# {{ __('Enrollment Proof Approved - 8th BCSMIF') }}

{{ __('Hello') }} {{ $registration->full_name }},

{{ __('We are pleased to inform you that your enrollment proof document for the 8th Brazilian Congress on Statistical Modeling in Insurance and Finance (8th BCSMIF) has been approved.') }}

## 🎓 {{ __('Enrollment Proof Validated!') }}

{{ __('Excellent! Your academic documentation has been verified and your enrollment status has been confirmed. Your registration process is now complete.') }}

## {{ __('Registration Summary') }}

**{{ __('Registration ID') }}:** #{{ $registration->id }}  
**{{ __('Registration Status') }}:** {{ __('Approved') }}
**{{ __('Academic Status') }}:** {{ __('Enrollment Verified') }}

@if($registration->events->count() > 0)
**{{ __('Selected Events') }}:**
@foreach($registration->events as $event)
- {{ $event->name }}
@endforeach
@endif

## {{ __('What This Means') }}

{{ __('Your enrollment proof has been successfully verified by our administrative team. This confirms that:') }}

- ✅ {{ __('Your academic documentation is valid and current') }}
- ✅ {{ __('Your enrollment status meets our requirements') }}
- ✅ {{ __('You are eligible to participate in all selected events') }}
- ✅ {{ __('Your registration is now fully processed') }}

## {{ __('Next Steps') }}

{{ __('Keep this email for your records. We will contact you soon with:') }}

- 📧 **{{ __('Event Details') }}**: {{ __('Venue locations, schedules, and logistics') }}
- 📚 **{{ __('Academic Materials') }}**: {{ __('Conference proceedings and resources') }}
- 🎯 **{{ __('Participation Instructions') }}**: {{ __('Check-in procedures and requirements') }}

@if(Route::has('my-registration'))
<x-mail::button :url="route('my-registration')" color="primary">
{{ __('View My Registration') }}
</x-mail::button>
@endif

## {{ __('Important Notes') }}

{{ __('Please ensure you bring a valid student ID or equivalent enrollment document to the event for verification purposes.') }}

{{ __('If your enrollment status changes before the event, please notify us immediately to avoid any registration complications.') }}

{{ __('If you have any questions or need assistance, please do not hesitate to contact our academic support team.') }}

{{ __('We look forward to your participation in the 8th BCSMIF!') }}

{{ __('Best regards') }},<br>
{{ __('Academic Committee of') }} {{ config('app.name') }}
</x-mail::message>