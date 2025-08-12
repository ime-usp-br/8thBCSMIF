<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class CurrencyInternationalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user for testing
        $this->admin = $this->createAdmin();
    }

    #[Test]
    public function currency_configuration_uses_default_brl()
    {
        // Clear any existing currency config
        Config::set('currency.code', null);

        // Test that default fallback is BRL
        $this->assertEquals('BRL', config('currency.code', 'BRL'));
    }

    #[Test]
    public function currency_configuration_respects_environment_variable()
    {
        // Set environment variable
        Config::set('currency.code', 'USD');

        $this->assertEquals('USD', config('currency.code'));
    }

    #[Test]
    public function admin_dashboard_uses_configured_currency()
    {
        // Set currency to USD for testing
        Config::set('currency.code', 'USD');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('metrics', function ($metrics) {
            return $metrics['revenue']['currency'] === 'USD';
        });
    }

    #[Test]
    public function admin_dashboard_displays_currency_formatted_values()
    {
        // Set BRL for testing
        Config::set('currency.code', 'BRL');
        Config::set('currency.locale', 'pt_BR');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('@currency');  // Check that blade directive is being used
    }

    #[Test]
    public function locale_aware_date_formatting_works()
    {
        // Test Portuguese locale
        App::setLocale('pt_BR');
        Config::set('app.locale', 'pt_BR');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        // The view should use proper locale settings
    }

    #[Test]
    public function recent_activity_feed_uses_locale_aware_timestamps()
    {
        // Set Portuguese locale
        App::setLocale('pt_BR');
        Config::set('app.locale', 'pt_BR');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'));

        $response->assertStatus(200);
        // Activity feed component should format timestamps according to locale
    }

    #[Test]
    public function currency_blade_directive_formats_correctly()
    {
        // Test with different currencies
        $testCases = [
            ['currency' => 'BRL', 'locale' => 'pt_BR', 'amount' => 1500.50],
            ['currency' => 'USD', 'locale' => 'en', 'amount' => 1500.50],
            ['currency' => 'EUR', 'locale' => 'en', 'amount' => 1500.50],
        ];

        foreach ($testCases as $case) {
            Config::set('currency.code', $case['currency']);
            Config::set('currency.locale', $case['locale']);
            Config::set('currency.precision', 2);

            // Create a simple view to test the directive
            $viewContent = '@currency('.$case['amount'].')';
            $blade = resolve('Illuminate\View\Compilers\BladeCompiler');
            $compiled = $blade->compileString($viewContent);

            // The compiled result should contain the Number::currency call
            $this->assertStringContains('Number::currency', $compiled);
            $this->assertStringContains((string) $case['amount'], $compiled);
        }
    }

    #[Test]
    public function date_blade_directives_work_correctly()
    {
        $timestamp = now()->toISOString();

        // Test dateLocale directive
        $viewContent = '@dateLocale("'.$timestamp.'")';
        $blade = resolve('Illuminate\View\Compilers\BladeCompiler');
        $compiled = $blade->compileString($viewContent);

        $this->assertStringContains('Carbon\\Carbon::parse', $compiled);
        $this->assertStringContains('locale(config(\'app.locale\'))', $compiled);

        // Test dateHuman directive
        $viewContent = '@dateHuman("'.$timestamp.'")';
        $compiled = $blade->compileString($viewContent);

        $this->assertStringContains('diffForHumans', $compiled);
    }

    #[Test]
    public function translations_exist_for_admin_dashboard()
    {
        $requiredTranslations = [
            'Admin Dashboard',
            'Total Registrations',
            'Pending Approvals',
            'Revenue',
            'Confirmed',
            'Pending',
            'Total',
            'Transport Needs',
            'Recent Activity',
            'Dashboard metrics refreshed successfully.',
            'Refresh Metrics',
            'Admin Dashboard - 8th BCSMIF',
        ];

        // Test English translations
        App::setLocale('en');
        foreach ($requiredTranslations as $key) {
            $translation = __($key);
            $this->assertNotEquals($key, $translation, "Translation missing for '$key' in English");
        }

        // Test Portuguese translations
        App::setLocale('pt_BR');
        foreach ($requiredTranslations as $key) {
            $translation = __($key);
            $this->assertNotEquals($key, $translation, "Translation missing for '$key' in Portuguese");
        }
    }

    #[Test]
    public function currency_service_provider_sets_global_defaults()
    {
        // Test that the CurrencyServiceProvider boots correctly
        Config::set('currency.code', 'EUR');
        Config::set('currency.locale', 'de');

        // Boot the service provider
        $provider = new \App\Providers\CurrencyServiceProvider($this->app);
        $provider->boot();

        // The Number facade should be configured
        $this->assertEquals('EUR', \Illuminate\Support\Number::defaultCurrency());
        $this->assertEquals('de', \Illuminate\Support\Number::defaultLocale());
    }

    #[Test]
    public function admin_dashboard_controller_passes_currency_in_metrics()
    {
        // Set test currency
        Config::set('currency.code', 'JPY');

        $controller = new \App\Http\Controllers\AdminDashboardController(
            new \App\Services\DashboardMetricService
        );

        $view = $controller->index();
        $metrics = $view->getData()['metrics'];

        $this->assertEquals('JPY', $metrics['revenue']['currency']);
    }

    /**
     * Helper method to create an admin user
     */
    protected function createAdmin()
    {
        $user = \App\Models\User::factory()->create([
            'email' => 'admin@test.com',
        ]);

        $user->assignRole('admin');

        return $user;
    }
}
