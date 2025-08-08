<?php

namespace Tests\Browser;

use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

class AdminDashboardTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Create roles if they don't exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'usp_user', 'guard_name' => 'web']);
    }

    public function test_admin_dashboard_accessible_via_navigation(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/registrations/my')
                ->clickLink($admin->name)
                ->waitFor('.dropdown')
                ->clickLink('Dashboard')
                ->assertRouteIs('admin.dashboard')
                ->assertSee('Admin Dashboard');
        });
    }

    public function test_admin_dashboard_renders_correctly(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Admin Dashboard')
                ->assertSee('Total Registrations')
                ->assertSee('Pending Approvals')
                ->assertSee('Revenue')
                ->assertSee('Transport Needs')
                ->assertSee('Registrations by Category')
                ->assertSee('Recent Activity')
                ->assertSee('Quick Actions');
        });
    }

    public function test_admin_dashboard_displays_metric_widgets(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.dashboard'))
                // Check for metric widgets
                ->assertSee('Total Registrations')
                ->assertSee('142') // placeholder count
                ->assertSee('Pending Approvals')
                ->assertSee('15') // placeholder payment proofs count
                ->assertSee('8')  // placeholder enrollment proofs count
                ->assertSee('Revenue')
                ->assertSee('R$ 45.750,00') // placeholder confirmed revenue
                ->assertSee('R$ 12.300,00') // placeholder pending revenue
                ->assertSee('Transport Needs')
                ->assertSee('23') // placeholder USP transport
                ->assertSee('18'); // placeholder GRU transport
        });
    }

    public function test_admin_dashboard_displays_category_breakdown(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Registrations by Category')
                ->assertSee('Undergrad Students')
                ->assertSee('Grad Students')
                ->assertSee('Professors')
                ->assertSee('Professionals')
                ->assertSee('45') // undergrad count
                ->assertSee('38') // grad count
                ->assertSee('28') // professor count
                ->assertSee('31'); // professional count
        });
    }

    public function test_admin_dashboard_displays_recent_activity(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Recent Activity')
                ->assertSee('New registration submitted')
                ->assertSee('Payment proof approved')
                ->assertSee('Enrollment proof uploaded')
                ->assertSee('Workshop registration')
                ->assertSee('Payment proof rejected')
                ->assertSee('2 min ago')
                ->assertSee('View all activities');
        });
    }

    public function test_admin_dashboard_quick_actions_work(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Quick Actions')
                ->assertSee('View Registrations')
                ->assertSee('Generate Reports')
                ->assertSee('Pending Approvals')
                ->clickLink('View Registrations')
                ->assertRouteIs('admin.registrations.index');
        });
    }

    public function test_admin_dashboard_responsive_layout(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->browse(function (Browser $browser) use ($admin) {
            // Test desktop layout
            $browser->loginAs($admin)
                ->visit(route('admin.dashboard'))
                ->resize(1200, 800)
                ->assertSee('Admin Dashboard')
                ->assertPresent('.grid') // Grid layout should be present
                ->assertPresent('.md\\:grid-cols-2'); // Medium screen grid columns

            // Test mobile layout
            $browser->resize(375, 667)
                ->assertSee('Admin Dashboard')
                ->assertPresent('.grid-cols-1'); // Should fall back to single column
        });
    }

    public function test_non_admin_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usp_user');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit(route('admin.dashboard'))
                ->assertSee('403'); // Should see forbidden error
        });
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit(route('admin.dashboard'))
                ->assertRouteIs('login.local'); // Should be redirected to login
        });
    }

    public function test_admin_dashboard_navigation_dropdown_contains_all_links(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/registrations/my')
                ->clickLink($admin->name)
                ->waitFor('.dropdown')
                ->assertSee('Dashboard')
                ->assertSee('Registrations')
                ->assertSee('Reports');
        });
    }

    public function test_admin_dashboard_trend_indicators_display(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit(route('admin.dashboard'))
                ->assertSee('Total Registrations')
                ->assertSee('+12.5%') // Trend percentage
                // Check for SVG trend arrow (up arrow for positive trend)
                ->assertPresent('svg');
        });
    }
}
