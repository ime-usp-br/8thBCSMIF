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

@if($registration->document_country_origin === 'BR' || $registration->document_country_origin === 'Brazil')
## Instruções para Pagamento

Por favor, efetue o pagamento via transferência bancária ou PIX para os dados abaixo:

**Dados Bancários:**
- **Banco:** Santander
- **Agência:** 0658
- **Conta Corrente:** 13006798-9
- **Favorecido:** Associação Brasileira de Estatística
- **CNPJ:** 56.572.456/0001-80

**Como enviar o comprovante:**
Após efetuar o pagamento, acesse sua conta no sistema e envie o comprovante de pagamento. Seu status será atualizado assim que a confirmação for processada.
@else
**Informação sobre Invoice:**
Uma invoice com detalhes para pagamento internacional será enviada em breve para seu e-mail.
@endif
@else
**Status do Pagamento:** Isento de taxa
@endif

## Próximos Passos

Mantenha este e-mail para seus registros. Entraremos em contato em breve com mais informações sobre o evento.

Atenciosamente,<br>
Organização do {{ config('app.name') }}
</x-mail::message>
