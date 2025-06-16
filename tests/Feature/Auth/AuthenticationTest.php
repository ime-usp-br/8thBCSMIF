<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Volt\Volt;
// use Mockery; // Mockery is implicitly used by Socialite::shouldReceive
use PHPUnit\Framework\Attributes\CoversClass; // Added for #[CoversClass]
use PHPUnit\Framework\Attributes\Group;      // Added for #[Group]
use PHPUnit\Framework\Attributes\Test;       // Added for #[Test]
use Tests\Fakes\FakeSenhaunicaSocialiteProvider;
use Tests\TestCase;
use Uspdev\SenhaunicaSocialite\Http\Controllers\SenhaunicaController; // Import the class to be covered

// Apply CoversClass at the class level
#[CoversClass(SenhaunicaController::class)]
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function login_screen_can_be_rendered(): void
    {
        // Teste original do Breeze para garantir que a rota local exista
        $response = $this->get('/login/local');

        $response
            ->assertOk()
            // Note: assertSeeVolt might not be standard; replaced with assertSeeLivewire if applicable,
            // or keep as is if using a specific Volt testing helper/assertion.
            // Assuming Volt renders a Livewire component, assertSeeLivewire might be more standard.
            // If Volt has its own assertion, keep it. Check Volt documentation if needed.
            // For now, keeping assertSeeVolt based on previous context.
            ->assertSeeVolt('pages.auth.login');
    }

    #[Test]
    public function users_can_authenticate_using_the_local_login_screen(): void
    {
        // Este teste atende ao Critério de Aceite 3 da Issue #31
        $user = User::factory()->create();

        // Testa o componente Livewire/Volt diretamente
        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password'); // Usa a senha padrão da factory

        // Chama a ação de login dentro do componente
        $component->call('login');

        // Verifica se não há erros de validação
        $component
            ->assertHasNoErrors()
            // Verifica se foi redirecionado para registrations.my
            ->assertRedirect(route('registrations.my', absolute: false));

        // Verifica se o usuário está autenticado
        $this->assertAuthenticated();
    }

    #[Test]
    public function email_must_be_a_valid_email_address_for_local_login(): void
    {
        // Este teste atende ao Critério de Aceite 4 da Issue #31
        Volt::test('pages.auth.login')
            ->set('form.email', 'invalid-email') // Formato inválido de e-mail
            ->set('form.password', 'password') // Senha qualquer, não será usada
            ->call('login')
            // Verifica especificamente o erro de validação da regra 'email' para o campo 'form.email'
            ->assertHasErrors(['form.email' => 'email'])
            // Garante que não há erros para o campo de senha neste cenário
            ->assertHasNoErrors(['form.password'])
            ->assertNoRedirect(); // Garante que não houve redirecionamento

        // Garante que o usuário não foi autenticado
        $this->assertGuest();
    }

    #[Test]
    public function users_can_not_authenticate_with_invalid_password_on_local_login(): void
    {
        // Este teste atende ao Critério de Aceite 5 da Issue #31
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password'); // Senha incorreta

        $component->call('login');

        // Verifica se há erro no campo de email (auth.failed é associado ao email geralmente)
        $component->assertHasErrors(['form.email' => trans('auth.failed')])
            // Garante que não há erro específico de validação de formato na senha neste caso
            ->assertHasNoErrors(['form.password']);

        $component->assertNoRedirect(); // Garante que não houve redirecionamento

        $this->assertGuest(); // Garante que o usuário não foi autenticado
    }

    #[Test]
    public function users_can_not_authenticate_with_non_existent_credentials_on_local_login(): void
    {
        // Este teste atende ao Critério de Aceite 6 da Issue #31
        $component = Volt::test('pages.auth.login')
            ->set('form.email', 'nonexistent@example.com') // Email válido em formato, mas não existente
            ->set('form.password', 'password'); // Senha qualquer

        $component->call('login');

        // Verifica se há erro no campo de email (auth.failed é associado ao email geralmente)
        $component->assertHasErrors(['form.email' => trans('auth.failed')])
            // Garante que não há erro específico de validação de formato na senha neste caso
            ->assertHasNoErrors(['form.password']);

        $component->assertNoRedirect(); // Garante que não houve redirecionamento

        $this->assertGuest(); // Garante que o usuário não foi autenticado
    }

    /**
     * Test if accessing the /login route triggers the Senhaunica Socialite redirect.
     *
     * Acceptance Criteria 7 (AC7) from Issue #31:
     * - Teste verifica se o acesso à rota `/login` (botão Senha Única) invoca o método correto do `SenhaunicaController` (ex: `redirectToProvider`). (Pode exigir mock do Socialite).
     */
    #[Test]
    #[Group('auth')]
    public function login_route_redirects_to_senhaunica_provider(): void
    {
        // Arrange: Mock the Socialite facade to return our Fake Provider instance
        // Use the Fake Provider to simulate the redirect without needing Mockery directly on the driver
        // This ensures our SenhaunicaController calls Socialite::driver('senhaunica')->redirect()
        $fakeProvider = new FakeSenhaunicaSocialiteProvider; // Instancia o Fake
        $fakeProvider->setRedirectUrl('https://expected-fake-redirect.usp.br'); // Define a URL esperada

        // Configura o Facade para retornar nossa instância fake quando o driver 'senhaunica' for chamado
        Socialite::shouldReceive('driver')
            ->with('senhaunica')
            ->once() // Garante que o driver foi chamado
            ->andReturn($fakeProvider); // Retorna nossa instância fake

        // Act: Make a GET request to the main login route (which now handles SenhaUnica)
        $response = $this->get(route('login')); // Use route() helper

        // Assert: Check if the response is a redirect to the URL defined in our Fake Provider
        $response->assertStatus(302);
        $response->assertRedirect('https://expected-fake-redirect.usp.br');

        // Mockery's expectation `->once()` verifies the driver was requested.
    }

    #[Test]
    public function navigation_menu_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->get('/my-registrations');

        $response
            ->assertOk()
            ->assertSee(__('My Registrations'))
            ->assertSee($user->name);
    }

    #[Test]
    public function users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirect('/');

        $this->assertGuest();
    }

    // New tests for AC3 of Issue #26
    #[Test]
    #[Group('auth-middleware')]
    public function unauthenticated_user_is_redirected_from_registrations_to_local_login(): void
    {
        // Ensure no user is authenticated
        $this->assertGuest();

        // Attempt to access the registrations route
        $response = $this->get(route('registrations.my'));

        // Assert that the user is redirected to the local login route
        $response->assertRedirect(route('login.local'));
    }

    #[Test]
    #[Group('auth-middleware')]
    public function unauthenticated_user_is_redirected_from_profile_to_local_login(): void
    {
        // Ensure no user is authenticated
        $this->assertGuest();

        // Attempt to access the profile route
        $response = $this->get(route('profile'));

        // Assert that the user is redirected to the local login route
        $response->assertRedirect(route('login.local'));
    }

    #[Test]
    #[Group('auth-middleware')]
    public function authenticated_user_can_access_registrations(): void
    {
        $user = User::factory()->verified()->create(); // Use verified for registrations access as per AC4

        $response = $this->actingAs($user)->get(route('registrations.my'));

        $response->assertOk();
        $response->assertSee(__('My Registrations')); // Check for registrations page text
    }

    // Test for AC4 (Issue #26) and AC10.2 (Issue #26)
    #[Test]
    #[Group('auth-middleware')]
    public function authenticated_unverified_user_is_redirected_from_registrations_to_verification_notice(): void
    {
        $user = User::factory()->unverified()->create(); // Create an unverified user

        $response = $this->actingAs($user)->get(route('registrations.my'));

        // Assert that the user is redirected to the email verification notice route
        $response->assertRedirect(route('verification.notice'));
    }

    #[Test]
    #[Group('auth-middleware')]
    public function authenticated_user_can_access_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile'));

        $response->assertOk();
        $response->assertSee(__('Profile')); // Check for a common profile text
    }

    // Test for AC5 of Issue #26
    #[Test]
    #[Group('auth-middleware')]
    public function authenticated_user_is_redirected_from_guest_routes_to_registrations(): void
    {
        $user = User::factory()->create(); // Could be verified or unverified, doesn't matter for guest middleware

        // Test redirection from /login/local
        $responseLogin = $this->actingAs($user)->get(route('login.local'));
        $responseLogin->assertRedirect(route('registrations.my', absolute: false));

        // Test redirection from /register
        $responseRegister = $this->actingAs($user)->get(route('register'));
        $responseRegister->assertRedirect(route('registrations.my', absolute: false));
    }
}
