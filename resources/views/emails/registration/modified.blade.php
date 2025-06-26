<x-mail::message>
# {{ __('Registration Modified - 8th BCSMIF') }}

{{ __('Hello') }} {{ $registration->full_name }},

{{ __('Your registration for the 8th Brazilian Congress on Statistical Modeling in Insurance and Finance (8th BCSMIF) has been successfully modified.') }}

## {{ __('Updated Registration Summary') }}

**{{ __('Selected Events') }}:**
@foreach($feeCalculation['details'] as $eventDetail)
- {{ $eventDetail['event_name'] }}: R$ {{ number_format($eventDetail['calculated_price'], 2, ',', '.') }}
@endforeach

@php
    $newTotalAmount = $feeCalculation['new_total_fee'];
    $totalPaid = $feeCalculation['total_paid'];
    $amountDue = $feeCalculation['amount_due'];
@endphp

**{{ __('New Total Amount') }}:** R$ {{ number_format($newTotalAmount, 2, ',', '.') }}

@if($totalPaid > 0)
**{{ __('Amount Already Paid') }}:** R$ {{ number_format($totalPaid, 2, ',', '.') }}
@endif

@if($amountDue > 0)
## {{ __('Payment Instructions') }}

{{ __('You have a pending payment for the additional amount due from the modification. Please complete payment to finalize your registration update.') }}

@if($registration->document_country_origin === 'BR' || $registration->document_country_origin === 'Brazil')
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
{{ __('An invoice with details for international payment will be sent to your email shortly.') }}
@endif
@else
**{{ __('Payment Status') }}:** {{ __('No additional payment required') }}
@endif

## {{ __('Next Steps') }}

{{ __('You can access your registration details at any time through your account on our system.') }}

<x-mail::button :url="route('registrations.my')">
{{ __('View My Registration') }}
</x-mail::button>

{{ __('Keep this email for your records. We will contact you soon with more information about the event.') }}

{{ __('Best regards') }},<br>
{{ __('Organization of') }} {{ config('app.name') }}

---

**{{ __('Registration ID') }}:** #{{ $registration->id }}  
**{{ __('Modification Date') }}:** {{ now()->format('d/m/Y H:i') }}
</x-mail::message>