<x-mail::message>
# Confirmação de Inscrição - 8th BCSMIF

Olá {{ $registration->full_name }},

Agradecemos sua inscrição para o 8º Congresso Brasileiro de Modelagem Estatística em Finanças e Seguros (8th BCSMIF)!

## Resumo da sua Inscrição

**Eventos Selecionados:**
@foreach($registration->events as $event)
- {{ $event->name }}: R$ {{ number_format((float) $event->pivot->price_at_registration, 2, ',', '.') }}
@endforeach

**Valor Total:** R$ {{ number_format((float) $registration->calculated_fee, 2, ',', '.') }}

@if($registration->calculated_fee > 0)
**Status do Pagamento:** {{ ucfirst(str_replace('_', ' ', $registration->payment_status)) }}

Em breve você receberá instruções detalhadas sobre como proceder com o pagamento.
@else
**Status do Pagamento:** Isento de taxa
@endif

## Próximos Passos

Mantenha este e-mail para seus registros. Entraremos em contato em breve com mais informações sobre o evento.

Atenciosamente,<br>
Organização do {{ config('app.name') }}
</x-mail::message>
