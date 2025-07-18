<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\VerifyEmail as VerifyEmailNotification;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        // Verifica se o componente Volt 'pages.auth.verify-email' está sendo renderizado
        $response
            ->assertSeeLivewire('pages.auth.verify-email') // Use assertSeeLivewire para componentes Livewire/Volt
            ->assertStatus(200);
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)] // Correção: Usar o email real do usuário para o hash
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('registrations.my', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')] // Hash inválido
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    // Teste para AC5 e AC6 (parte do reenvio e exibição de mensagem)
    public function test_verification_link_can_be_resent_and_status_is_shown(): void
    {
        $user = User::factory()->unverified()->create();

        // Mock para evitar envio real de email
        Notification::fake();

        // Monta o componente Livewire/Volt e atua como o usuário não verificado
        $response = Livewire::actingAs($user)
            ->test('pages.auth.verify-email') // Referencia o componente Volt pelo nome da view
            ->call('sendVerification'); // Chama a ação de reenviar

        // Verifica se a notificação de verificação foi enviada para o usuário correto
        Notification::assertSentTo(
            $user,
            VerifyEmailNotification::class
        );

        // AC6: Verifica se a mensagem de status (agora renderizada pelo componente) está presente na resposta do Livewire
        // A chave de tradução é resolvida para o idioma padrão (en) durante o teste.
        $response->assertSeeHtml(__('A new verification link has been sent to the email address you provided during registration.'));
    }

    // Novo teste de cenário: usuário já verificado tentando reenviar
    public function test_resend_redirects_if_already_verified(): void
    {
        // Cria um usuário já verificado (estado padrão da factory)
        $user = User::factory()->create();

        Notification::fake();

        Livewire::actingAs($user)
            ->test('pages.auth.verify-email')
            ->call('sendVerification')
            // Verifica se foi redirecionado para o registrations.my
            // O assertRedirect do Livewire verifica o próximo request após a ação
            ->assertRedirect(route('registrations.my', absolute: false));

        // Garante que nenhuma notificação foi enviada
        Notification::assertNothingSent();
        // Garante que a mensagem de status não foi definida
        $this->assertNull(session('status'));
    }

    /**
     * Test that only one verification email is sent during registration.
     */
    public function test_single_verification_email_sent_during_registration(): void
    {
        // Seed roles needed for registration
        $this->seed(RoleSeeder::class);

        Notification::fake();

        // Simulate user registration using Livewire
        $response = Livewire::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'Password123!')
            ->set('sou_da_usp', false)
            ->call('register');

        $response->assertRedirect('/my-registration');

        // Verify user was created
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // Check that exactly one verification notification was sent
        Notification::assertSentTo($user, VerifyEmailNotification::class);
        Notification::assertSentToTimes($user, VerifyEmailNotification::class, 1);
    }

    /**
     * Test that no duplicate verification emails are sent when Registered event is fired multiple times.
     */
    public function test_no_duplicate_emails_when_registered_event_fired_multiple_times(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        // Clear cache before test
        Cache::flush();

        // Fire the Registered event multiple times
        event(new \Illuminate\Auth\Events\Registered($user));
        event(new \Illuminate\Auth\Events\Registered($user));
        event(new \Illuminate\Auth\Events\Registered($user));

        // Should only send one verification email due to caching mechanism
        Notification::assertSentTo($user, VerifyEmailNotification::class);
        Notification::assertSentToTimes($user, VerifyEmailNotification::class, 1);
    }

    /**
     * Test that no verification email is sent to already verified users.
     */
    public function test_no_verification_email_sent_to_already_verified_users(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // Fire the Registered event
        event(new \Illuminate\Auth\Events\Registered($user));

        // Should not send any verification email
        Notification::assertNotSentTo($user, VerifyEmailNotification::class);
    }
}
