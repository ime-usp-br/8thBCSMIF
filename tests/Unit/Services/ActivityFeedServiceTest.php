<?php

namespace Tests\Unit\Services;

use App\Models\EnrollmentProof;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use App\Services\ActivityFeedService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ActivityFeedServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityFeedService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ActivityFeedService;
    }

    /** @test */
    public function it_returns_recent_activity_with_default_limit()
    {
        // Create test data
        $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'payment_status' => 'pending',
            'created_at' => Carbon::now()->subMinutes(5),
        ]);

        $activities = $this->service->getRecentActivity();

        $this->assertInstanceOf(Collection::class, $activities);
        $this->assertGreaterThan(0, $activities->count());

        $firstActivity = $activities->first();
        $this->assertArrayHasKey('id', $firstActivity);
        $this->assertArrayHasKey('type', $firstActivity);
        $this->assertArrayHasKey('title', $firstActivity);
        $this->assertArrayHasKey('description', $firstActivity);
        $this->assertArrayHasKey('timestamp', $firstActivity);
        $this->assertArrayHasKey('user_name', $firstActivity);
        $this->assertArrayHasKey('user_email', $firstActivity);
        $this->assertArrayHasKey('status', $firstActivity);
        $this->assertArrayHasKey('link_url', $firstActivity);
        $this->assertArrayHasKey('link_text', $firstActivity);
    }

    /** @test */
    public function it_includes_recent_registrations_in_activity_feed()
    {
        $user = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

        Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Jane Doe',
            'payment_status' => 'pending',
            'created_at' => Carbon::now()->subMinutes(10),
        ]);

        $activities = $this->service->getRecentActivity();

        $registrationActivity = $activities->firstWhere('type', 'registration_submission');

        $this->assertNotNull($registrationActivity);
        $this->assertEquals('New Registration Submission', $registrationActivity['title']);
        $this->assertStringContainsString('Jane Doe', $registrationActivity['description']);
        $this->assertEquals('Jane Doe', $registrationActivity['user_name']);
        $this->assertEquals('jane@example.com', $registrationActivity['user_email']);
        $this->assertEquals('pending', $registrationActivity['status']);
    }

    /** @test */
    public function it_includes_recent_payment_proofs_in_activity_feed()
    {
        $user = User::factory()->create(['name' => 'Bob Smith', 'email' => 'bob@example.com']);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Bob Smith',
        ]);

        Payment::factory()->create([
            'registration_id' => $registration->id,
            'amount' => 150.00,
            'status' => 'pending_approval',
            'payment_proof_path' => 'payments/proof.pdf',
            'updated_at' => Carbon::now()->subMinutes(5),
        ]);

        $activities = $this->service->getRecentActivity();

        $paymentActivity = $activities->firstWhere('type', 'payment_proof_upload');

        $this->assertNotNull($paymentActivity);
        $this->assertEquals('Payment Proof Uploaded', $paymentActivity['title']);
        $this->assertStringContainsString('Bob Smith', $paymentActivity['description']);
        $this->assertStringContainsString('150,00', $paymentActivity['description']);
        $this->assertEquals('Bob Smith', $paymentActivity['user_name']);
        $this->assertEquals('bob@example.com', $paymentActivity['user_email']);
        $this->assertEquals('pending_approval', $paymentActivity['status']);
    }

    /** @test */
    public function it_includes_recent_enrollment_proofs_in_activity_feed()
    {
        $user = User::factory()->create(['name' => 'Alice Johnson', 'email' => 'alice@example.com']);

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Alice Johnson',
        ]);

        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => 'pending_approval',
            'uploaded_at' => Carbon::now()->subMinutes(15),
            'original_filename' => 'enrollment.pdf',
        ]);

        $activities = $this->service->getRecentActivity();

        $enrollmentActivity = $activities->firstWhere('type', 'enrollment_proof_submission');

        $this->assertNotNull($enrollmentActivity);
        $this->assertEquals('Enrollment Proof Submitted', $enrollmentActivity['title']);
        $this->assertStringContainsString('Alice Johnson', $enrollmentActivity['description']);
        $this->assertEquals('Alice Johnson', $enrollmentActivity['user_name']);
        $this->assertEquals('alice@example.com', $enrollmentActivity['user_email']);
        $this->assertEquals('pending_approval', $enrollmentActivity['status']);
    }

    /** @test */
    public function it_sorts_activities_by_timestamp_descending()
    {
        $user = User::factory()->create();

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create activities with different timestamps - more separated
        $oldRegistration = Registration::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subMinutes(30), // Oldest
        ]);

        $payment = Payment::factory()->create([
            'registration_id' => $registration->id,
            'payment_proof_path' => 'payments/proof.pdf',
            'updated_at' => Carbon::now(), // Most recent
        ]);

        $enrollmentProof = EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'uploaded_at' => Carbon::now()->subMinutes(15), // Middle
        ]);

        $activities = $this->service->getRecentActivity();

        // Verify activities are sorted by timestamp (most recent first)
        $this->assertTrue($activities->count() >= 3);

        // Find the activities we created
        $paymentActivity = $activities->firstWhere('type', 'payment_proof_upload');
        $enrollmentActivity = $activities->firstWhere('type', 'enrollment_proof_submission');
        $oldRegistrationActivity = $activities->where('type', 'registration_submission')
            ->where('id', 'registration_'.$oldRegistration->id)
            ->first();

        $this->assertNotNull($paymentActivity);
        $this->assertNotNull($enrollmentActivity);
        $this->assertNotNull($oldRegistrationActivity);

        // Check that payment (most recent) comes before enrollment (middle)
        $paymentIndex = $activities->search(fn ($item) => $item['type'] === 'payment_proof_upload');
        $enrollmentIndex = $activities->search(fn ($item) => $item['type'] === 'enrollment_proof_submission');
        $registrationIndex = $activities->search(fn ($item) => $item['id'] === 'registration_'.$oldRegistration->id);

        $this->assertLessThan($enrollmentIndex, $paymentIndex);
        $this->assertLessThan($registrationIndex, $enrollmentIndex);
    }

    /** @test */
    public function it_respects_the_limit_parameter()
    {
        $user = User::factory()->create();

        // Create more activities than the limit
        Registration::factory()->count(8)->create(['user_id' => $user->id]);

        $activities = $this->service->getRecentActivity(5);

        $this->assertEquals(5, $activities->count());
    }

    /** @test */
    public function it_excludes_payments_without_proof_paths()
    {
        $user = User::factory()->create();

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create payment without proof path (should be excluded)
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'payment_proof_path' => null,
        ]);

        // Create payment with proof path (should be included)
        Payment::factory()->create([
            'registration_id' => $registration->id,
            'payment_proof_path' => 'payments/proof.pdf',
        ]);

        $activities = $this->service->getRecentActivity();
        $paymentActivities = $activities->where('type', 'payment_proof_upload');

        $this->assertEquals(1, $paymentActivities->count());
    }

    /** @test */
    public function it_excludes_enrollment_proofs_without_uploaded_at()
    {
        $user = User::factory()->create();

        $registration = Registration::factory()->create([
            'user_id' => $user->id,
        ]);

        // Create enrollment proof without uploaded_at (should be excluded)
        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'uploaded_at' => null,
        ]);

        // Create enrollment proof with uploaded_at (should be included)
        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'uploaded_at' => Carbon::now(),
        ]);

        $activities = $this->service->getRecentActivity();
        $enrollmentActivities = $activities->where('type', 'enrollment_proof_submission');

        $this->assertEquals(1, $enrollmentActivities->count());
    }

    /** @test */
    public function it_returns_activity_counts_for_last_24_hours()
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        // Recent activities (within 24 hours)
        Registration::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subHours(12),
        ]);

        Payment::factory()->create([
            'registration_id' => $registration->id,
            'payment_proof_path' => 'payments/proof.pdf',
            'updated_at' => Carbon::now()->subHours(6),
        ]);

        EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'uploaded_at' => Carbon::now()->subHours(3),
        ]);

        // Old activities (older than 24 hours - should not be counted)
        Registration::factory()->create([
            'user_id' => $user->id,
            'created_at' => Carbon::now()->subDays(2),
        ]);

        $counts = $this->service->getActivityCounts();

        $this->assertArrayHasKey('registrations', $counts);
        $this->assertArrayHasKey('payments', $counts);
        $this->assertArrayHasKey('enrollment_proofs', $counts);

        $this->assertEquals(2, $counts['registrations']); // Registration + original from factory
        $this->assertEquals(1, $counts['payments']);
        $this->assertEquals(1, $counts['enrollment_proofs']);
    }

    /** @test */
    public function it_returns_correct_activity_icons()
    {
        $this->assertEquals('fas fa-user-plus text-blue-500', $this->service->getActivityIcon('registration_submission'));
        $this->assertEquals('fas fa-credit-card text-green-500', $this->service->getActivityIcon('payment_proof_upload'));
        $this->assertEquals('fas fa-graduation-cap text-purple-500', $this->service->getActivityIcon('enrollment_proof_submission'));
        $this->assertEquals('fas fa-bell text-gray-500', $this->service->getActivityIcon('unknown_type'));
    }

    /** @test */
    public function it_returns_correct_status_badge_classes()
    {
        $this->assertEquals('bg-yellow-100 text-yellow-800', $this->service->getStatusBadgeClass('pending'));
        $this->assertEquals('bg-orange-100 text-orange-800', $this->service->getStatusBadgeClass('pending_approval'));
        $this->assertEquals('bg-green-100 text-green-800', $this->service->getStatusBadgeClass('approved'));
        $this->assertEquals('bg-red-100 text-red-800', $this->service->getStatusBadgeClass('rejected'));
        $this->assertEquals('bg-gray-100 text-gray-800', $this->service->getStatusBadgeClass('unknown_status'));
    }

    /** @test */
    public function it_returns_correct_status_text()
    {
        $this->assertEquals('Pending', $this->service->getStatusText('pending'));
        $this->assertEquals('Pending Approval', $this->service->getStatusText('pending_approval'));
        $this->assertEquals('Approved', $this->service->getStatusText('approved'));
        $this->assertEquals('Rejected', $this->service->getStatusText('rejected'));
        $this->assertEquals('Unknown_status', $this->service->getStatusText('unknown_status'));
    }

    /** @test */
    public function it_generates_correct_admin_links()
    {
        $user = User::factory()->create();
        $registration = Registration::factory()->create(['user_id' => $user->id]);

        $activities = $this->service->getRecentActivity();
        $activity = $activities->first();

        $this->assertStringContainsString('admin/registrations/', $activity['link_url']);
        $this->assertStringContainsString((string) $registration->id, $activity['link_url']);
        $this->assertEquals('View Registration', $activity['link_text']);
    }
}
