<x-mail::message>
# {{ __('Payment Rejected - 8th BCSMIF') }}

{{ __('Hello') }} {{ $registration->full_name }},

{{ __('We have reviewed your payment proof for the 8th Brazilian Congress on Statistical Modeling in Insurance and Finance (8th BCSMIF) and unfortunately we cannot approve it at this time.') }}

## ❌ {{ __('Payment Proof Not Approved') }}

{{ __('Our administrative team has carefully reviewed your submission and identified the following issue that needs to be addressed:') }}

**{{ __('Rejection Reason') }}:**
> {{ $rejectionReason }}

## {{ __('Registration Summary') }}

**{{ __('Registration ID') }}:** #{{ $registration->id }}  
**{{ __('Registration Status') }}:** {{ __('Rejected - Action Required') }}
**{{ __('Total Amount Due') }}:** R$ {{ number_format($registration->calculateCorrectTotalFee(), 2, ',', '.') }}

@if($registration->events->count() > 0)
**{{ __('Selected Events') }}:**
@foreach($registration->events as $event)
- {{ $event->name }}: R$ {{ number_format((float) $event->pivot->price_at_registration, 2, ',', '.') }}
@endforeach
@endif

## {{ __('Next Steps - How to Resolve') }}

{{ __('Don\'t worry! You can easily resolve this issue and complete your registration by following these steps:') }}

1. **{{ __('Review the rejection reason above carefully') }}**
2. **{{ __('Prepare a new payment proof that addresses the identified issue') }}**  
3. **{{ __('Upload the corrected document using the button below') }}**

{{ __('Common issues and solutions:') }}
- **{{ __('Poor image quality') }}**: {{ __('Please ensure the document is clear and readable') }}
- **{{ __('Incorrect amount') }}**: {{ __('Verify the payment amount matches your registration total') }}
- **{{ __('Wrong document type') }}**: {{ __('Submit official payment receipt or bank statement') }}
- **{{ __('Missing information') }}**: {{ __('Ensure all payment details are visible') }}

<x-mail::button :url="route('my-registration')" color="primary">
{{ __('Upload New Payment Proof') }}
</x-mail::button>

## {{ __('Need Help?') }}

{{ __('If you have any questions about this rejection or need assistance with your payment proof, please don\'t hesitate to contact our support team. We\'re here to help you complete your registration successfully.') }}

{{ __('Your registration will remain in our system, and you can resubmit your payment proof at any time through your registration page.') }}

{{ __('Thank you for your understanding and we look forward to your participation in the 8th BCSMIF.') }}

{{ __('Best regards') }},<br>
{{ __('Organization of') }} {{ config('app.name') }}
</x-mail::message>