<x-mail::message>
# {{ __('Comprovante de Pagamento Enviado - 8th BCSMIF') }}

## {{ __('Notificação de Upload') }}

{{ __('O participante') }} **{{ $registration->full_name }}** ({{ $registration->user->email }}) {{ __('da inscrição') }} **#{{ $registration->id }}** {{ __('anexou um comprovante de pagamento') }}.

## {{ __('Detalhes da Inscrição') }}

**{{ __('Participante') }}:** {{ $registration->full_name }}  
**{{ __('E-mail') }}:** {{ $registration->user->email }}  
**{{ __('Documento') }}:** {{ $registration->cpf ?: $registration->passport_number }} ({{ $registration->document_country_origin }})  
**{{ __('Data de Upload') }}:** {{ now()->format('d/m/Y H:i') }}

## {{ __('Eventos Selecionados') }}

@foreach($registration->events as $event)
- **{{ $event->name }}**  
  {{ __('Preço na inscrição') }}: R$ {{ number_format((float) $event->pivot->price_at_registration, 2, ',', '.') }}
@endforeach

## {{ __('Informações Financeiras') }}

@if($registration->events->isNotEmpty())
**{{ __('Valor Total') }}:** R$ {{ number_format($registration->events->sum('pivot.price_at_registration'), 2, ',', '.') }}  
@endif
**{{ __('Status do Pagamento') }}:** {{ ucfirst(str_replace('_', ' ', $registration->payment_status)) }}

## {{ __('Ação Necessária') }}

{{ __('Para visualizar o comprovante anexado e aprovar/rejeitar o pagamento, acesse o painel administrativo') }}:

<x-mail::button :url="config('app.url') . '/admin/registrations/' . $registration->id">
{{ __('Visualizar Comprovante no Painel Admin') }}
</x-mail::button>

---

**{{ __('ID da Inscrição') }}:** #{{ $registration->id }}  
**{{ __('Sistema') }}:** {{ config('app.name') }}
</x-mail::message>