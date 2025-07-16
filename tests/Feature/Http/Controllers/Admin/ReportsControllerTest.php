<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\EnrollmentProof;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        Role::create(['name' => 'admin', 'guard_name' => 'web']);
        Role::create(['name' => 'usp_user', 'guard_name' => 'web']);
    }

    // ===== AUTHENTICATION AND AUTHORIZATION TESTS =====

    public function test_admin_reports_index_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.index'));

        $response->assertRedirect(route('login.local'));
    }

    public function test_admin_reports_index_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usp_user');

        $response = $this->actingAs($user)->get(route('admin.reports.index'));

        $response->assertStatus(403);
    }

    public function test_admin_reports_index_allows_admin_access(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.index');
    }

    public function test_admin_reports_enrollment_proofs_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.enrollment-proofs'));

        $response->assertRedirect(route('login.local'));
    }

    public function test_admin_reports_enrollment_proofs_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usp_user');

        $response = $this->actingAs($user)->get(route('admin.reports.enrollment-proofs'));

        $response->assertStatus(403);
    }

    public function test_admin_reports_enrollment_proofs_allows_admin_access(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.reports.enrollment-proofs'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.enrollment-proofs');
    }

    public function test_admin_reports_payments_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.payments'));

        $response->assertRedirect(route('login.local'));
    }

    public function test_admin_reports_payments_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usp_user');

        $response = $this->actingAs($user)->get(route('admin.reports.payments'));

        $response->assertStatus(403);
    }

    public function test_admin_reports_payments_allows_admin_access(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.reports.payments'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.payments');
    }

    public function test_admin_reports_auto_approved_requires_authentication(): void
    {
        $response = $this->get(route('admin.reports.auto-approved'));

        $response->assertRedirect(route('login.local'));
    }

    public function test_admin_reports_auto_approved_requires_admin_role(): void
    {
        $user = User::factory()->create();
        $user->assignRole('usp_user');

        $response = $this->actingAs($user)->get(route('admin.reports.auto-approved'));

        $response->assertStatus(403);
    }

    public function test_admin_reports_auto_approved_allows_admin_access(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.reports.auto-approved'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.auto-approved');
    }

    // ===== AC17 TESTS: ADMIN VISUALIZA COMPROVANTES DE MATRÍCULA PENDENTES =====

    public function test_admin_reports_index_displays_enrollment_proofs_statistics(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create enrollment proofs with different statuses
        $pendingProof = EnrollmentProof::factory()->create([
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);
        $approvedProof = EnrollmentProof::factory()->create([
            'status' => EnrollmentProof::STATUS_APPROVED,
        ]);
        $rejectedProof = EnrollmentProof::factory()->create([
            'status' => EnrollmentProof::STATUS_REJECTED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.index');
        $response->assertViewHas('enrollmentProofsStats');

        $enrollmentProofsStats = $response->viewData('enrollmentProofsStats');
        $this->assertEquals(3, $enrollmentProofsStats['total']);
        $this->assertEquals(1, $enrollmentProofsStats['pending']);
        $this->assertEquals(1, $enrollmentProofsStats['approved']);
        $this->assertEquals(1, $enrollmentProofsStats['rejected']);
    }

    public function test_admin_reports_index_shows_pending_enrollment_proofs_count_in_view(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create 3 pending enrollment proofs
        EnrollmentProof::factory()->count(3)->create([
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);

        // Create 2 approved enrollment proofs
        EnrollmentProof::factory()->count(2)->create([
            'status' => EnrollmentProof::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee(__('Enrollment Proofs'));
        $response->assertSee(__('Pending'));
        $response->assertSee('3'); // Pending count
        $response->assertSee('2'); // Approved count
        $response->assertSee('5'); // Total count
    }

    public function test_admin_reports_index_displays_enrollment_proofs_card_with_correct_styling(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertSee('border-usp-blue-pri', false);
        $response->assertSee('text-yellow-600', false);
        $response->assertSee('text-green-600', false);
        $response->assertSee('text-red-600', false);
        $response->assertSee(route('admin.reports.enrollment-proofs'));
        $response->assertSee(__('View Details'));
    }

    public function test_admin_reports_enrollment_proofs_page_displays_pending_proofs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create user and registration for the enrollment proof
        $user = User::factory()->create([
            'name' => 'Test Student',
            'email' => 'student@example.com',
        ]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'Test Student',
            'registration_category_snapshot' => 'undergraduate_student',
        ]);

        // Create events for the registration
        $event = Event::factory()->create([
            'code' => 'BCSMIF2025',
            'name' => '8th BCSMIF Conference',
        ]);
        $registration->events()->attach($event->code, ['price_at_registration' => 0.00]);

        // Create pending enrollment proof
        $pendingProof = EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
            'original_filename' => 'enrollment_proof.pdf',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.enrollment-proofs'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.enrollment-proofs');
        $response->assertViewHas('enrollmentProofs');
        $response->assertViewHas('stats');

        // Check that the pending proof is displayed
        $enrollmentProofs = $response->viewData('enrollmentProofs');
        $this->assertCount(1, $enrollmentProofs);
        $this->assertEquals($pendingProof->id, $enrollmentProofs->first()->id);
    }

    public function test_admin_reports_enrollment_proofs_page_filters_by_pending_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create enrollment proofs with different statuses
        $pendingProof = EnrollmentProof::factory()->create([
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);
        $approvedProof = EnrollmentProof::factory()->create([
            'status' => EnrollmentProof::STATUS_APPROVED,
        ]);
        $rejectedProof = EnrollmentProof::factory()->create([
            'status' => EnrollmentProof::STATUS_REJECTED,
        ]);

        // Filter by pending status
        $response = $this->actingAs($admin)->get(route('admin.reports.enrollment-proofs', [
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.reports.enrollment-proofs');

        $enrollmentProofs = $response->viewData('enrollmentProofs');
        $this->assertCount(1, $enrollmentProofs);
        $this->assertEquals($pendingProof->id, $enrollmentProofs->first()->id);
        $this->assertEquals(EnrollmentProof::STATUS_PENDING_APPROVAL, $enrollmentProofs->first()->status);
    }

    public function test_admin_reports_enrollment_proofs_page_displays_correct_relationships(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create user and registration with detailed data
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);
        $registration = Registration::factory()->create([
            'user_id' => $user->id,
            'full_name' => 'John Doe',
            'registration_category_snapshot' => 'undergraduate_student',
        ]);

        // Create events for the registration
        $event1 = Event::factory()->create([
            'code' => 'BCSMIF2025',
            'name' => '8th BCSMIF Conference',
        ]);
        $event2 = Event::factory()->create([
            'code' => 'WORKSHOP2025',
            'name' => 'Workshop 2025',
        ]);
        $registration->events()->attach([
            $event1->code => ['price_at_registration' => 0.00],
            $event2->code => ['price_at_registration' => 0.00],
        ]);

        // Create enrollment proof
        $enrollmentProof = EnrollmentProof::factory()->create([
            'registration_id' => $registration->id,
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
            'original_filename' => 'enrollment_proof.pdf',
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.enrollment-proofs'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.enrollment-proofs');

        $enrollmentProofs = $response->viewData('enrollmentProofs');
        $this->assertCount(1, $enrollmentProofs);

        $proof = $enrollmentProofs->first();
        $this->assertEquals($registration->id, $proof->registration_id);
        $this->assertEquals($user->id, $proof->registration->user_id);
        $this->assertEquals('John Doe', $proof->registration->full_name);
        $this->assertEquals(2, $proof->registration->events->count());
    }

    public function test_admin_reports_enrollment_proofs_page_includes_approved_by_relationship(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $approver = User::factory()->create([
            'name' => 'Admin Approver',
            'email' => 'approver@admin.com',
        ]);

        // Create approved enrollment proof
        $approvedProof = EnrollmentProof::factory()->create([
            'status' => EnrollmentProof::STATUS_APPROVED,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.enrollment-proofs'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.enrollment-proofs');

        $enrollmentProofs = $response->viewData('enrollmentProofs');
        $this->assertCount(1, $enrollmentProofs);

        $proof = $enrollmentProofs->first();
        $this->assertEquals($approver->id, $proof->approved_by);
        $this->assertEquals('Admin Approver', $proof->approvedBy->name);
    }

    public function test_admin_reports_enrollment_proofs_page_supports_date_filtering(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create enrollment proofs with different dates
        $oldProof = EnrollmentProof::factory()->create([
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
            'created_at' => now()->subDays(5),
        ]);
        $recentProof = EnrollmentProof::factory()->create([
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
            'created_at' => now()->subDays(1),
        ]);

        // Filter by date from 2 days ago
        $response = $this->actingAs($admin)->get(route('admin.reports.enrollment-proofs', [
            'date_from' => now()->subDays(2)->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.reports.enrollment-proofs');

        $enrollmentProofs = $response->viewData('enrollmentProofs');
        $this->assertCount(1, $enrollmentProofs);
        $this->assertEquals($recentProof->id, $enrollmentProofs->first()->id);
    }

    public function test_admin_reports_enrollment_proofs_page_supports_pagination(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create more than 20 enrollment proofs to test pagination
        EnrollmentProof::factory()->count(25)->create([
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.enrollment-proofs'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.enrollment-proofs');

        $enrollmentProofs = $response->viewData('enrollmentProofs');
        $this->assertEquals(20, $enrollmentProofs->perPage());
        $this->assertEquals(25, $enrollmentProofs->total());
        $this->assertEquals(2, $enrollmentProofs->lastPage());
    }

    // ===== STATISTICS AND INTEGRATION TESTS =====

    public function test_admin_reports_index_stats_reflect_actual_database_state(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create enrollment proofs
        EnrollmentProof::factory()->count(5)->create([
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);
        EnrollmentProof::factory()->count(3)->create([
            'status' => EnrollmentProof::STATUS_APPROVED,
        ]);
        EnrollmentProof::factory()->count(2)->create([
            'status' => EnrollmentProof::STATUS_REJECTED,
        ]);

        // Create payments
        Payment::factory()->count(4)->create([
            'status' => 'pending',
        ]);
        Payment::factory()->count(6)->create([
            'status' => 'approved',
            'amount' => 100.00,
        ]);

        // Create auto-approved payments
        Payment::factory()->count(2)->create([
            'status' => 'approved',
            'amount' => 0.00,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.index'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.index');

        // Check enrollment proofs stats
        $enrollmentProofsStats = $response->viewData('enrollmentProofsStats');
        $this->assertEquals(10, $enrollmentProofsStats['total']);
        $this->assertEquals(5, $enrollmentProofsStats['pending']);
        $this->assertEquals(3, $enrollmentProofsStats['approved']);
        $this->assertEquals(2, $enrollmentProofsStats['rejected']);

        // Check payments stats
        $paymentsStats = $response->viewData('paymentsStats');
        $this->assertEquals(12, $paymentsStats['total']); // 4 pending + 6 approved + 2 auto-approved
        $this->assertEquals(4, $paymentsStats['pending']);
        $this->assertEquals(8, $paymentsStats['approved']); // 6 regular + 2 auto-approved

        // Check auto-approved stats
        $autoApprovedStats = $response->viewData('autoApprovedStats');
        $this->assertEquals(2, $autoApprovedStats['total']);
    }

    public function test_admin_reports_enrollment_proofs_statistics_are_consistent(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create enrollment proofs with different statuses
        EnrollmentProof::factory()->count(3)->create([
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
        ]);
        EnrollmentProof::factory()->count(2)->create([
            'status' => EnrollmentProof::STATUS_APPROVED,
        ]);
        EnrollmentProof::factory()->count(1)->create([
            'status' => EnrollmentProof::STATUS_REJECTED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.enrollment-proofs'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.enrollment-proofs');

        $stats = $response->viewData('stats');
        $this->assertEquals(6, $stats['total']);
        $this->assertEquals(3, $stats['pending']);
        $this->assertEquals(2, $stats['approved']);
        $this->assertEquals(1, $stats['rejected']);
    }

    public function test_admin_reports_enrollment_proofs_shows_empty_state_when_no_proofs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.reports.enrollment-proofs'));

        $response->assertOk();
        $response->assertViewIs('admin.reports.enrollment-proofs');

        $enrollmentProofs = $response->viewData('enrollmentProofs');
        $this->assertCount(0, $enrollmentProofs);

        $stats = $response->viewData('stats');
        $this->assertEquals(0, $stats['total']);
        $this->assertEquals(0, $stats['pending']);
        $this->assertEquals(0, $stats['approved']);
        $this->assertEquals(0, $stats['rejected']);
    }

    public function test_admin_reports_enrollment_proofs_combined_filters_work_correctly(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create enrollment proofs with different statuses and dates
        $targetProof = EnrollmentProof::factory()->create([
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
            'created_at' => now()->subDays(1),
        ]);

        // This should be filtered out by status
        EnrollmentProof::factory()->create([
            'status' => EnrollmentProof::STATUS_APPROVED,
            'created_at' => now()->subDays(1),
        ]);

        // This should be filtered out by date
        EnrollmentProof::factory()->create([
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
            'created_at' => now()->subDays(5),
        ]);

        // Apply combined filters
        $response = $this->actingAs($admin)->get(route('admin.reports.enrollment-proofs', [
            'status' => EnrollmentProof::STATUS_PENDING_APPROVAL,
            'date_from' => now()->subDays(2)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.reports.enrollment-proofs');

        $enrollmentProofs = $response->viewData('enrollmentProofs');
        $this->assertCount(1, $enrollmentProofs);
        $this->assertEquals($targetProof->id, $enrollmentProofs->first()->id);
    }
}
