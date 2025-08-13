<?php

namespace App\Console\Commands;

use App\Models\Registration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FixRegistrationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registrations:fix-status 
                           {--dry-run : Show what would be changed without actually updating}
                           {--chunk=100 : Number of registrations to process at once}
                           {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix registration status based on related payments and enrollment proofs';

    /**
     * Statistics tracking
     */
    private int $totalProcessed = 0;

    private int $totalChanged = 0;

    private int $totalErrors = 0;

    /** @var array<int, array<string, mixed>> */
    private array $changes = [];

    /** @var array<int, array<string, mixed>> */
    private array $errors = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔧 Registration Status Fix Utility');
        $this->newLine();

        // Show current environment and warning
        $this->displayEnvironmentWarning();

        // Get options
        $isDryRun = $this->option('dry-run');
        $chunkSize = (int) $this->option('chunk');
        $force = $this->option('force');

        // Validate chunk size
        if ($chunkSize < 1 || $chunkSize > 1000) {
            $this->error('Chunk size must be between 1 and 1000');

            return Command::FAILURE;
        }

        // Get total count
        $totalRegistrations = Registration::count();

        if ($totalRegistrations === 0) {
            $this->info('No registrations found to process.');

            return Command::SUCCESS;
        }

        $this->info("Found {$totalRegistrations} registrations to process");
        $this->info("Processing in chunks of {$chunkSize}");

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->newLine();

        // Confirmation (skip if dry-run or force)
        if (! $isDryRun && ! $force && ! $this->confirmExecution()) {
            $this->info('Operation cancelled.');

            return Command::SUCCESS;
        }

        // Process registrations
        $startTime = microtime(true);
        $this->processRegistrations($chunkSize, $isDryRun);
        $endTime = microtime(true);

        // Display results
        $this->displayResults($endTime - $startTime, $isDryRun);

        return $this->totalErrors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Display environment warning and backup recommendation
     */
    private function displayEnvironmentWarning(): void
    {
        $environment = app()->environment();

        if ($environment === 'production') {
            $this->error('⚠️  PRODUCTION ENVIRONMENT DETECTED');
            $this->warn('Strongly recommend creating a database backup before proceeding!');
            $this->newLine();
        } else {
            $this->info("Environment: {$environment}");
        }
    }

    /**
     * Confirm execution with user
     */
    private function confirmExecution(): bool
    {
        return $this->confirm(
            'This will update registration status for all records. Are you sure you want to continue?',
            false
        );
    }

    /**
     * Process all registrations in chunks
     */
    private function processRegistrations(int $chunkSize, bool $isDryRun): void
    {
        $totalRegistrations = Registration::count();
        $progressBar = $this->output->createProgressBar($totalRegistrations);
        $progressBar->setFormat('Processing: %current%/%max% [%bar%] %percent:3s%% %memory:6s%');

        Registration::with(['payments', 'enrollmentProof'])
            ->chunk($chunkSize, function ($registrations) use ($progressBar, $isDryRun) {
                foreach ($registrations as $registration) {
                    $this->processRegistration($registration, $isDryRun);
                    $progressBar->advance();
                }
            });

        $progressBar->finish();
        $this->newLine(2);
    }

    /**
     * Process individual registration
     */
    private function processRegistration(Registration $registration, bool $isDryRun): void
    {
        try {
            $this->totalProcessed++;

            $currentStatus = $registration->status;
            $calculatedStatus = $registration->calculateStatusFromRelatedModels();

            if ($currentStatus !== $calculatedStatus) {
                $this->totalChanged++;

                $changeRecord = [
                    'id' => $registration->id,
                    'user_email' => $registration->email,
                    'category' => $registration->registration_category_snapshot,
                    'current_status' => $currentStatus,
                    'new_status' => $calculatedStatus,
                    'timestamp' => now()->toISOString(),
                ];

                $this->changes[] = $changeRecord;

                if (! $isDryRun) {
                    // Use updateQuietly to prevent observer loops
                    $registration->updateQuietly(['status' => $calculatedStatus]);

                    // Log the change
                    Log::info('Registration status updated', $changeRecord);
                }
            }
        } catch (\Exception $e) {
            $this->totalErrors++;
            $errorRecord = [
                'id' => $registration->id ?? 'unknown',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ];

            $this->errors[] = $errorRecord;
            Log::error('Failed to process registration status', $errorRecord);
        }
    }

    /**
     * Display operation results
     */
    private function displayResults(float $executionTime, bool $isDryRun): void
    {
        $this->info('📊 Operation Results:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Processed', number_format($this->totalProcessed)],
                ['Status Changes', number_format($this->totalChanged)],
                ['Errors', number_format($this->totalErrors)],
                ['Execution Time', number_format($executionTime, 2).'s'],
                ['Memory Peak', $this->formatBytes(memory_get_peak_usage(true))],
            ]
        );

        if ($this->totalChanged > 0) {
            $this->newLine();
            if ($isDryRun) {
                $this->info('🔍 Changes that would be made:');
            } else {
                $this->info('✅ Changes applied:');
            }

            $this->displayChanges();
        }

        if ($this->totalErrors > 0) {
            $this->newLine();
            $this->error('❌ Errors encountered:');
            $this->displayErrors();
        }

        if (! $isDryRun && $this->totalChanged > 0) {
            $this->newLine();
            $this->info('🔍 To verify changes, you can run:');
            $this->line('   php artisan registrations:fix-status --dry-run');
        }
    }

    /**
     * Display detailed changes
     */
    private function displayChanges(): void
    {
        if (count($this->changes) <= 10) {
            // Show all changes if there are few
            $this->table(
                ['ID', 'Email', 'Category', 'Current', 'New'],
                array_map(function ($change) {
                    return [
                        $change['id'],
                        $change['user_email'],
                        $change['category'],
                        $change['current_status'],
                        $change['new_status'],
                    ];
                }, $this->changes)
            );
        } else {
            // Show summary and first few changes
            $this->table(
                ['ID', 'Email', 'Category', 'Current', 'New'],
                array_map(function ($change) {
                    return [
                        $change['id'],
                        $change['user_email'],
                        $change['category'],
                        $change['current_status'],
                        $change['new_status'],
                    ];
                }, array_slice($this->changes, 0, 5))
            );

            $remaining = count($this->changes) - 5;
            if ($remaining > 0) {
                $this->line("... and {$remaining} more changes");
            }
        }
    }

    /**
     * Display errors
     */
    private function displayErrors(): void
    {
        $this->table(
            ['ID', 'Error'],
            array_map(function ($error) {
                $errorMessage = is_string($error['error']) ? $error['error'] : 'Unknown error';
                return [
                    $error['id'],
                    strlen($errorMessage) > 60 ? substr($errorMessage, 0, 57).'...' : $errorMessage,
                ];
            }, array_slice($this->errors, 0, 10))
        );

        if (count($this->errors) > 10) {
            $this->line('... and '.(count($this->errors) - 10).' more errors');
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.2f %s', $bytes / pow(1024, $factor), $units[$factor] ?? 'TB');
    }
}
