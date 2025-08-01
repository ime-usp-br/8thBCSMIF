<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Setup method to run before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure the shared database file exists
        $this->ensureSharedDatabaseExists();

        // Clean up all test users from previous runs
        \App\Models\User::whereIn('email', [
            'test@example.com',
            'external@example.com',
            'usp@usp.br',
            'testuser@usp.br',
            'unverified@example.com',
            'nonexistent@example.com',
        ])->orWhere('email', 'LIKE', 'test@%')
            ->orWhere('email', 'LIKE', '%@example.com')
            ->orWhere('email', 'LIKE', '%@usp.br')
            ->delete();

        // Ensure seeders are run if tables are empty
        if (\Spatie\Permission\Models\Role::count() === 0) {
            $this->seed(\Database\Seeders\RoleSeeder::class);
            $this->seed(\Database\Seeders\EventsTableSeeder::class);
            $this->seed(\Database\Seeders\FeesTableSeeder::class);
        }
    }

    /**
     * Ensure the shared database file exists and is accessible
     */
    protected function ensureSharedDatabaseExists(): void
    {
        $databasePath = database_path('testing/shared.sqlite');

        // Create directory if it doesn't exist
        if (! file_exists(dirname($databasePath))) {
            mkdir(dirname($databasePath), 0755, true);
        }

        // Create empty database file if it doesn't exist
        if (! file_exists($databasePath)) {
            touch($databasePath);
        }
    }

    /**
     * Create a test user that will be available to both test context and browser context
     */
    protected function createTestUser(array $attributes = []): \App\Models\User
    {
        // Generate unique email if not provided
        if (! isset($attributes['email'])) {
            $timestamp = now()->format('His');
            $random = rand(1000, 9999);
            $attributes['email'] = "test{$timestamp}{$random}@example.com";
        }

        $defaultAttributes = [
            'name' => 'Test User',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ];

        $attributes = array_merge($defaultAttributes, $attributes);

        // Create user using factory (this will use the shared SQLite database)
        $user = \App\Models\User::factory()->create($attributes);

        // Assign default role if not specified
        if (! $user->roles()->exists()) {
            $user->assignRole('external_user');
        }

        return $user;
    }

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        // Automatically startChromeDriver based on SAIL detection (no change needed here)
        if (! static::runningInSail()) {
            static::startChromeDriver();
        }
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-web-security',
            '--disable-features=VizDisplayCompositor',
            '--no-first-run',
            '--disable-default-apps',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding',
        ])->unless($this->hasHeadlessDisabled(), function ($items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        $driver = RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );

        // Balanced timeouts for reliability
        $driver->manage()->timeouts()->implicitlyWait(15);  // Aumentado para 15s
        $driver->manage()->timeouts()->pageLoadTimeout(30); // Aumentado para 30s
        $driver->manage()->timeouts()->setScriptTimeout(20); // Aumentado para 20s

        return $driver;
    }

    /**
     * Determine whether the Dusk command has disabled headless mode.
     */
    protected function hasHeadlessDisabled(): bool
    {
        return isset($_SERVER['DUSK_HEADLESS_DISABLED']) ||
               isset($_ENV['DUSK_HEADLESS_DISABLED']);
    }

    /**
     * Determine if the browser window should start maximized.
     */
    protected function shouldStartMaximized(): bool
    {
        return isset($_SERVER['DUSK_START_MAXIMIZED']) ||
               isset($_ENV['DUSK_START_MAXIMIZED']);
    }
}
