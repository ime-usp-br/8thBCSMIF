<x-mail::message>
@if(isset($proofType) && $proofType === 'enrollment')
# {{ __('Enrollment Proof Uploaded - 8th BCSMIF') }}
@else
# {{ __('Payment Proof Uploaded - 8th BCSMIF') }}
@endif

## {{ __('Upload Notification') }}

@if(isset($proofType) && $proofType === 'enrollment')
{{ __('The participant') }} **{{ $registration->full_name }}** ({{ $registration->user->email }}) {{ __('from registration') }} **#{{ $registration->id }}** {{ __('uploaded an enrollment proof') }}.
@else
{{ __('The participant') }} **{{ $registration->full_name }}** ({{ $registration->user->email }}) {{ __('from registration') }} **#{{ $registration->id }}** {{ __('uploaded a payment proof') }}.
@endif

## {{ __('Registration Details') }}

**{{ __('Participant') }}:** {{ $registration->full_name }}  
**{{ __('Email') }}:** {{ $registration->user->email }}  
**{{ __('Document') }}:** {{ $registration->cpf ?: $registration->passport_number }} ({{ $registration->document_country_origin }})  
**{{ __('Upload Date') }}:** {{ now()->format('d/m/Y H:i') }}

## {{ __('Selected Events') }}

@foreach($registration->events as $event)
- **{{ $event->name }}**  
  {{ __('Price at registration') }}: R$ {{ number_format((float) $event->pivot->price_at_registration, 2, ',', '.') }}
@endforeach

@if(!isset($proofType) || $proofType !== 'enrollment')
## {{ __('Financial Information') }}

@if($registration->events->isNotEmpty())
**{{ __('Total Amount') }}:** R$ {{ number_format($registration->events->sum('pivot.price_at_registration'), 2, ',', '.') }}  
@endif
**{{ __('Status') }}:** {{ ucfirst(str_replace('_', ' ', $registration->status)) }}
@endif

## {{ __('Required Action') }}

@if(isset($proofType) && $proofType === 'enrollment')
{{ __('To view the attached enrollment proof and approve/reject it, please access the admin panel') }}:
@else
{{ __('To view the attached payment proof and approve/reject the payment, please access the admin panel') }}:
@endif

<x-mail::button :url="config('app.url') . '/admin/registrations/' . $registration->id">
@if(isset($proofType) && $proofType === 'enrollment')
{{ __('View Enrollment Proof in Admin Panel') }}
@else
{{ __('View Payment Proof in Admin Panel') }}
@endif
</x-mail::button>

---

**{{ __('Registration ID') }}:** #{{ $registration->id }}  
**{{ __('System') }}:** {{ config('app.name') }}
</x-mail::message>
