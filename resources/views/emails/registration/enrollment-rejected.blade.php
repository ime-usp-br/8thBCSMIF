<x-mail::message>
# {{ __('Enrollment Proof Rejected - 8th BCSMIF') }}

{{ __('Hello') }} {{ $registration->full_name }},

{{ __('We have reviewed your enrollment proof document for the 8th Brazilian Congress on Statistical Modeling in Insurance and Finance (8th BCSMIF) and unfortunately we cannot approve it at this time.') }}

## ❌ {{ __('Enrollment Document Not Approved') }}

{{ __('Our academic verification team has carefully reviewed your submission and identified the following issue that needs to be addressed:') }}

**{{ __('Rejection Reason') }}:**
> {{ $rejectionReason }}

## {{ __('Registration Summary') }}

**{{ __('Registration ID') }}:** #{{ $registration->id }}  
**{{ __('Registration Status') }}:** {{ __('Pending - Document Resubmission Required') }}
**{{ __('Academic Status') }}:** {{ __('Enrollment Verification Needed') }}

@if($registration->events->count() > 0)
**{{ __('Selected Events') }}:**
@foreach($registration->events as $event)
- {{ $event->name }}
@endforeach
@endif

## {{ __('Next Steps - Document Resubmission') }}

{{ __('Don\'t worry! You can easily resolve this issue and complete your registration by following these steps:') }}

1. **{{ __('Review the rejection reason above carefully') }}**
2. **{{ __('Prepare a corrected enrollment document that addresses the identified issue') }}**  
3. **{{ __('Upload the new document using the button below') }}**

### {{ __('Common Issues and Solutions') }}

- **{{ __('Poor document quality') }}**: {{ __('Ensure the document is clear, readable, and high-resolution') }}
- **{{ __('Expired enrollment') }}**: {{ __('Submit a current enrollment certificate or transcript') }}
- **{{ __('Incomplete information') }}**: {{ __('Verify all required fields and dates are visible') }}
- **{{ __('Wrong document type') }}**: {{ __('Submit official enrollment certificate, transcript, or student ID') }}
- **{{ __('Institution not recognized') }}**: {{ __('Provide additional verification from your academic institution') }}

### {{ __('Acceptable Documents') }}

✅ **{{ __('Official enrollment certificate from your institution') }}**  
✅ **{{ __('Current academic transcript with enrollment status') }}**  
✅ **{{ __('Official student ID with validity dates') }}**  
✅ **{{ __('Letter from academic advisor confirming enrollment') }}**

@if(Route::has('my-registration'))
<x-mail::button :url="route('my-registration')" color="primary">
{{ __('Upload New Enrollment Proof') }}
</x-mail::button>
@endif

## {{ __('Academic Support') }}

{{ __('If you have questions about acceptable documents or need assistance with your enrollment verification, please contact our academic support team. We\'re here to help you complete your registration successfully.') }}

**{{ __('Important') }}**: {{ __('Your registration will remain in our system, and you can resubmit your enrollment proof at any time through your registration page. There is no deadline penalty for resubmission.') }}

## {{ __('Need Help?') }}

{{ __('Common questions we can help with:') }}

- 🎓 {{ __('What documents are acceptable for enrollment verification?') }}
- 📄 {{ __('How to obtain official enrollment certificates from your institution?') }}
- 🔍 {{ __('Document quality requirements and formatting guidelines?') }}
- 📧 {{ __('Alternative submission methods if online upload fails?') }}

{{ __('Thank you for your understanding and patience. We look forward to your successful participation in the 8th BCSMIF.') }}

{{ __('Best regards') }},<br>
{{ __('Academic Verification Committee of') }} {{ config('app.name') }}
</x-mail::message>