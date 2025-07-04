<?php

use App\Exceptions\ReplicadoServiceException; // Import custom exception
use App\Models\User;
use App\Services\ReplicadoService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log; // Keep for potential direct logging if needed
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

/**
* Componente Livewire/Volt para a página de registro de usuários.
*
* Gerencia o formulário de registro, incluindo a lógica condicional para usuários USP
* com validação de Número USP (codpes) e e-mail via ReplicadoService.
*/
new #[Layout('layouts.guest')] class extends Component {
    /** @var string O nome completo do usuário. */
    public string $name = '';

    /** @var string O endereço de e-mail do usuário. */
    public string $email = '';

    /** @var string A senha escolhida pelo usuário. */
    public string $password = '';

    /** @var string A confirmação da senha. */
    public string $password_confirmation = '';

    /** @var bool Indica se o usuário se declara como membro da USP. */
    public bool $sou_da_usp = false;

    /** @var string O Número USP (codpes) do usuário, se aplicável. */
    public string $codpes = '';

    /**
    * Hook executado quando a propriedade `$email` é atualizada.
    *
    * Marca automaticamente o checkbox "Sou da USP" se o e-mail terminar com `usp.br`.
    *
    * @param string $value O novo valor do e-mail.
    * @return void
    */
    public function updatedEmail(string $value): void
    {
        if (str_ends_with(strtolower($value), 'usp.br')) {
            $this->sou_da_usp = true;
        }
    }

    /**
    * Define as regras de validação para o formulário de registro.
    *
    * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
    */
    public function rules(): array
    {
        return [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
        'sou_da_usp' => ['boolean'],
        'codpes' => [
        Rule::requiredIf($this->sou_da_usp),
        'nullable',
        'numeric',
        'digits_between:6,8',
        function (string $attribute, mixed $value, Closure $fail) {
            if ($this->sou_da_usp && !empty($value)) {
                $replicadoService = app(ReplicadoService::class);
                try {
                    if (!$replicadoService->validarNuspEmail((int)$value, $this->email)) {
                        // AC4: Fail validation if Replicado validation returns false
                        $fail('validation.custom.codpes.replicado_validation_failed');
                    }
                } catch (ReplicadoServiceException $e) { // Catch specific exception
                    // AC5: Handle Replicado service communication failure.
                    // Logging is already done within ReplicadoService.
                    // Return a generic validation error message to the user.
                    $fail('validation.custom.codpes.replicado_service_unavailable');
                } catch (\Exception $e) {
                    // Catch any other unexpected exceptions from the service call
                    Log::error('Unexpected error during Replicado validation: '.$e->getMessage(), ['exception' => $e]);
                    $fail('validation.custom.codpes.replicado_service_unavailable'); // Still show a generic error to user
                }
            }
        },
        ],
        'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ];
    }


    /**
    * Processa a submissão do formulário de registro.
    *
    * Valida os dados, cria o usuário, atribui o role apropriado (usp_user ou external_user),
    * dispara o evento Registered, realiza o login e redireciona para a página de registrations.
    *
    * @return void
    *
    * @throws \Illuminate\Validation\ValidationException
    */
    public function register(): void
    {
        $validated = $this->validate();

        $userData = [
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'codpes' => ($this->sou_da_usp && isset($validated['codpes'])) ? $validated['codpes'] : null,
        ];

        $user = User::create($userData);

        // Assign role based on whether codpes was successfully validated and stored
        if ($user->codpes !== null) {
            // This implies $this->sou_da_usp was true, codpes was provided,
            // and Replicado validation was successful.
            $user->assignRole('usp_user'); // AC7
        } else {
            // This covers:
            // 1. User is not from USP ($this->sou_da_usp was false).
            // 2. User claimed to be from USP, but Replicado validation failed (mismatch or service error).
            $user->assignRole('external_user'); // AC8
        }

        event(new Registered($user));

        Auth::login($user);

        $this->redirect(route('registrations.my', absolute: false), navigate: true);
    }
}; ?>


{{-- Alpine data for form reactivity --}}
<div x-data="{
    email: @entangle('email'),
    sou_da_usp: @entangle('sou_da_usp'),
    get isUspEmail() {
        return this.email.toLowerCase().endsWith('usp.br');
    },
    get showCodpes() {
        return this.isUspEmail || this.sou_da_usp;
    }
}">
<div class="flex justify-center mb-4">
<a href="/" wire:navigate>
<img src="{{ Vite::asset('resources/images/ime/logo-vertical-simplificada-padrao.png') }}" alt="Logo IME-USP" class="w-20 h-auto block dark:hidden" dusk="ime-logo-light">
<img src="{{ Vite::asset('resources/images/ime/logo-vertical-simplificada-branca.png') }}" alt="Logo IME-USP" class="w-20 h-auto hidden dark:block" dusk="ime-logo-dark">
</a>
</div>

<form wire:submit="register">
    <!-- Name -->
    <div>
        <x-input-label for="name" :value="__('Name')" dusk="name-label" />
        <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autofocus autocomplete="name" dusk="name-input" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" dusk="name-error" />
    </div>

    <!-- Email Address -->
    <div class="mt-4">
        <x-input-label for="email" :value="__('Email')" dusk="email-label" />
        {{-- Use wire:model.blur to update Livewire state less frequently --}}
        <x-text-input wire:model.blur="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" dusk="email-input" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" dusk="email-error" />
    </div>

    {{-- --- ADDED USP FIELDS --- --}}
    <!-- "Sou da USP" Checkbox -->
    <div class="block mt-4">
        <label for="sou_da_usp" class="inline-flex items-center">
            {{-- AC13: Uses existing dusk selector 'is-usp-user-checkbox' from previous commit --}}
            <input wire:model.live="sou_da_usp" {{-- Use .live for immediate conditional logic --}}
                   id="sou_da_usp"
                   type="checkbox"
                   {{-- Disable checkbox if email is already a USP email --}}
                   x-bind:disabled="isUspEmail"
                   class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800 disabled:opacity-50 disabled:cursor-not-allowed"
                   name="sou_da_usp"
                   dusk="is-usp-user-checkbox">
            <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('I\'m from USP') }}</span>
        </label>
         <x-input-error :messages="$errors->get('sou_da_usp')" class="mt-2" dusk="sou_da_usp-error" />
    </div>

    <!-- Número USP (codpes) Field - Conditional -->
    {{-- Show if email ends with @usp.br OR the checkbox is checked --}}
    <div x-show="showCodpes" 
         x-cloak 
         x-transition
         class="mt-4" 
         dusk="codpes-container">
        <x-input-label for="codpes" :value="__('USP Number (codpes)')" dusk="codpes-label" />
        <x-text-input wire:model="codpes" id="codpes" class="block mt-1 w-full"
                      type="text" {{-- Use text, validation handles numeric --}}
                      inputmode="numeric" {{-- Hint for mobile keyboards --}}
                      name="codpes"
                      autocomplete="off"
                      x-bind:required="showCodpes"
                      dusk="codpes-input" />
        <x-input-error :messages="$errors->get('codpes')" class="mt-2" dusk="codpes-error" />
    </div>
    {{-- --- END ADDED USP FIELDS --- --}}


    <!-- Password -->
    <div class="mt-4">
        <x-input-label for="password" :value="__('Password')" dusk="password-label" />
        <x-text-input wire:model="password" id="password" class="block mt-1 w-full"
                        type="password"
                        name="password"
                        required autocomplete="new-password" dusk="password-input" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" dusk="password-error" />
    </div>

    <!-- Confirm Password -->
    <div class="mt-4">
        <x-input-label for="password_confirmation" :value="__('Confirm Password')" dusk="password-confirmation-label" />
        <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                        type="password"
                        name="password_confirmation" required autocomplete="new-password" dusk="password-confirmation-input" />
        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" dusk="password-confirmation-error" />
    </div>

    <div class="flex items-center justify-end mt-4">
        <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('login.local') }}" wire:navigate dusk="already-registered-link">
            {{ __('Already registered?') }}
        </a>
        <x-primary-button class="ms-4" dusk="register-button">
            {{ __('Register') }}
        </x-primary-button>
    </div>
</form>

</div>