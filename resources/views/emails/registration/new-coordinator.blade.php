<x-mail::message>
# {{ __('Nova Inscrição - 8th BCSMIF') }}

## {{ __('Detalhes da Inscrição') }}

**{{ __('Participante') }}:** {{ $registration->full_name }}  
**{{ __('E-mail') }}:** {{ $registration->user->email }}  
**{{ __('Documento') }}:** {{ $registration->cpf ?: $registration->passport_number }} ({{ $registration->document_country_origin }})  
**{{ __('Data da Inscrição') }}:** {{ $registration->created_at->format('d/m/Y H:i') }}

## {{ __('Eventos Selecionados') }}

@foreach($registration->events as $event)
- **{{ $event->name }}**  
  {{ __('Preço na inscrição') }}: R$ {{ number_format((float) $event->pivot->price_at_registration, 2, ',', '.') }}
@endforeach

## {{ __('Informações Financeiras') }}

**{{ __('Valor Total') }}:** R$ {{ number_format($registration->events->sum('pivot.price_at_registration'), 2, ',', '.') }}  
**{{ __('Status do Pagamento') }}:** {{ ucfirst(str_replace('_', ' ', $registration->payment_status)) }}

## {{ __('Informações Pessoais') }}

**{{ __('Gênero') }}:** {{ $registration->gender ?: __('Não informado') }}  
**{{ __('Data de Nascimento') }}:** {{ $registration->date_of_birth ? \Carbon\Carbon::parse($registration->date_of_birth)->format('d/m/Y') : __('Não informada') }}  
**{{ __('Telefone') }}:** {{ $registration->phone_number ?: __('Não informado') }}

**{{ __('Endereço') }}:**  
{{ $registration->address_street }}  
{{ $registration->address_city }}, {{ $registration->address_state_province }} - {{ $registration->address_postal_code }}  
{{ $registration->address_country }}

## {{ __('Informações Acadêmicas/Profissionais') }}

**{{ __('Categoria') }}:** {{ ucfirst(str_replace('_', ' ', $registration->position)) }}  
**{{ __('Instituição') }}:** {{ $registration->affiliation ?: __('Não informada') }}

## {{ __('Informações Especiais') }}

**{{ __('Restrições Alimentares') }}:** {{ $registration->dietary_restrictions === 'none' ? __('Nenhuma') : $registration->dietary_restrictions }}
@if($registration->dietary_restrictions === 'other' && $registration->other_dietary_restrictions)
**{{ __('Outras Restrições') }}:** {{ $registration->other_dietary_restrictions }}
@endif

**{{ __('Contato de Emergência') }}:** {{ $registration->emergency_contact_name }} ({{ $registration->emergency_contact_relationship }}) - {{ $registration->emergency_contact_phone }}

@if($registration->requires_visa_letter)
**⚠️ {{ __('Solicita carta para visto') }}**
@endif

## {{ __('Painel Administrativo') }}

{{ __('Para visualizar mais detalhes e gerenciar esta inscrição, acesse o painel administrativo') }}:

<x-mail::button :url="config('app.url') . '/admin/registrations/' . $registration->id">
{{ __('Ver Inscrição no Painel Admin') }}
</x-mail::button>

---

**{{ __('ID da Inscrição') }}:** #{{ $registration->id }}  
**{{ __('Sistema') }}:** {{ config('app.name') }}
</x-mail::message>