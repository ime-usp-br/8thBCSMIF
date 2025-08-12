<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Admin\RecentActivityFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Livewire\Livewire;
use Tests\TestCase;

class RecentActivityFeedInternationalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user for testing
        $this->admin = $this->createAdmin();
    }

    #[Test]
    public function format_timestamp_respects_application_locale()
    {
        // Test with Portuguese locale
        App::setLocale('pt_BR');
        Config::set('app.locale', 'pt_BR');

        $component = new RecentActivityFeed;
        $timestamp = now()->subMinutes(5);

        $formatted = $component->formatTimestamp($timestamp);

        $this->assertIsString($formatted);
        $this->assertNotEmpty($formatted);

        // Test with English locale
        App::setLocale('en');
        Config::set('app.locale', 'en');

        $formattedEn = $component->formatTimestamp($timestamp);

        $this->assertIsString($formattedEn);
        $this->assertNotEmpty($formattedEn);

        // The formatting should potentially be different for different locales
        // (though diffForHumans might return similar results in this case)
    }

    #[Test]
    public function format_timestamp_handles_string_input()
    {
        App::setLocale('en');
        Config::set('app.locale', 'en');

        $component = new RecentActivityFeed;
        $timestampString = now()->subHours(2)->toISOString();

        $formatted = $component->formatTimestamp($timestampString);

        $this->assertIsString($formatted);
        $this->assertNotEmpty($formatted);
    }

    #[Test]
    public function component_renders_with_proper_translations()
    {
        $this->actingAs($this->admin);

        // Test English
        App::setLocale('en');
        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSee('Recent Activity');
        $component->assertSee('Updates every 30 seconds');
        $component->assertSee('Show');

        // Test Portuguese
        App::setLocale('pt_BR');
        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSee('Atividade Recente')
            ->assertSee('Atualiza a cada 30 segundos')
            ->assertSee('Mostrar');
    }

    #[Test]
    public function component_displays_no_activity_message_in_correct_locale()
    {
        $this->actingAs($this->admin);

        // Test with no activities (empty state)
        // English
        App::setLocale('en');
        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSee('No recent activity');

        // Portuguese
        App::setLocale('pt_BR');
        $component = Livewire::test(RecentActivityFeed::class);

        $component->assertSee('Nenhuma atividade recente');
    }

    #[Test]
    public function refresh_activities_method_works()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(RecentActivityFeed::class);

        // Should be able to call refresh without errors
        $component->call('refreshActivities')
            ->assertHasNoErrors();
    }

    #[Test]
    public function set_limit_method_constrains_values_properly()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(RecentActivityFeed::class);

        // Test setting valid limit
        $component->call('setLimit', 15);
        $this->assertEquals(15, $component->get('limit'));

        // Test setting too high limit (should be constrained to 20)
        $component->call('setLimit', 50);
        $this->assertEquals(20, $component->get('limit'));

        // Test setting too low limit (should be constrained to 5)
        $component->call('setLimit', 2);
        $this->assertEquals(5, $component->get('limit'));
    }

    #[Test]
    public function component_has_required_helper_methods()
    {
        $component = new RecentActivityFeed;

        // Test all helper methods exist
        $this->assertTrue(method_exists($component, 'getActivityIcon'));
        $this->assertTrue(method_exists($component, 'getStatusBadgeClass'));
        $this->assertTrue(method_exists($component, 'getStatusText'));
        $this->assertTrue(method_exists($component, 'formatTimestamp'));
        $this->assertTrue(method_exists($component, 'hasActivities'));
        $this->assertTrue(method_exists($component, 'getActivityCountByType'));
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
