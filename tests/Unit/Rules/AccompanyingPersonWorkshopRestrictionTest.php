<?php

namespace Tests\Unit\Rules;

use App\Models\Event;
use App\Rules\AccompanyingPersonWorkshopRestriction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccompanyingPersonWorkshopRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected Event $mainConferenceEvent;

    protected Event $workshopEvent;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test events
        $this->mainConferenceEvent = Event::factory()->create([
            'code' => 'BCSMIF2025',
            'name' => 'Main Conference',
            'is_main_conference' => true,
        ]);

        $this->workshopEvent = Event::factory()->create([
            'code' => 'WORKSHOP_1',
            'name' => 'Test Workshop',
            'is_main_conference' => false,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_when_accompanying_person_selects_workshop(): void
    {
        $rule = new AccompanyingPersonWorkshopRestriction;
        $rule->setData([
            'registration_category_snapshot' => 'accompanying_person',
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('selected_event_codes', ['WORKSHOP_1'], function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('Accompanying persons cannot register for workshops', $failMessage);
        $this->assertStringContainsString('Test Workshop', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_fails_when_accompanying_person_selects_multiple_workshops(): void
    {
        $workshop2 = Event::factory()->create([
            'code' => 'WORKSHOP_2',
            'name' => 'Second Workshop',
            'is_main_conference' => false,
        ]);

        $rule = new AccompanyingPersonWorkshopRestriction;
        $rule->setData([
            'registration_category_snapshot' => 'accompanying_person',
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('selected_event_codes', ['WORKSHOP_1', 'WORKSHOP_2'], function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('Accompanying persons cannot register for workshops', $failMessage);
        $this->assertStringContainsString('Test Workshop', $failMessage);
        $this->assertStringContainsString('Second Workshop', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_when_accompanying_person_selects_main_conference_only(): void
    {
        $rule = new AccompanyingPersonWorkshopRestriction;
        $rule->setData([
            'registration_category_snapshot' => 'accompanying_person',
        ]);

        $failed = false;

        $rule->validate('selected_event_codes', ['BCSMIF2025'], function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_passes_when_accompanying_person_selects_main_conference_and_workshop_but_rule_prevents_it(): void
    {
        $rule = new AccompanyingPersonWorkshopRestriction;
        $rule->setData([
            'registration_category_snapshot' => 'accompanying_person',
        ]);

        $failed = false;
        $failMessage = '';

        $rule->validate('selected_event_codes', ['BCSMIF2025', 'WORKSHOP_1'], function ($message) use (&$failed, &$failMessage) {
            $failed = true;
            $failMessage = $message;
        });

        $this->assertTrue($failed);
        $this->assertStringContainsString('Test Workshop', $failMessage);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_skips_validation_for_non_accompanying_person(): void
    {
        $rule = new AccompanyingPersonWorkshopRestriction;
        $rule->setData([
            'registration_category_snapshot' => 'professional_foreign',
        ]);

        $failed = false;

        $rule->validate('selected_event_codes', ['WORKSHOP_1'], function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_skips_validation_when_category_is_missing(): void
    {
        $rule = new AccompanyingPersonWorkshopRestriction;
        $rule->setData([]);

        $failed = false;

        $rule->validate('selected_event_codes', ['WORKSHOP_1'], function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_skips_validation_when_selected_events_is_not_array(): void
    {
        $rule = new AccompanyingPersonWorkshopRestriction;
        $rule->setData([
            'registration_category_snapshot' => 'accompanying_person',
        ]);

        $failed = false;

        $rule->validate('selected_event_codes', 'not_an_array', function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_skips_validation_when_selected_events_is_empty(): void
    {
        $rule = new AccompanyingPersonWorkshopRestriction;
        $rule->setData([
            'registration_category_snapshot' => 'accompanying_person',
        ]);

        $failed = false;

        $rule->validate('selected_event_codes', [], function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_invalid_event_codes_gracefully(): void
    {
        $rule = new AccompanyingPersonWorkshopRestriction;
        $rule->setData([
            'registration_category_snapshot' => 'accompanying_person',
        ]);

        $failed = false;

        $rule->validate('selected_event_codes', ['INVALID_EVENT_CODE'], function ($message) use (&$failed) {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_allows_student_categories_to_register_for_workshops(): void
    {
        $studentCategories = ['undergrad_student', 'grad_student'];

        foreach ($studentCategories as $category) {
            $rule = new AccompanyingPersonWorkshopRestriction;
            $rule->setData([
                'registration_category_snapshot' => $category,
            ]);

            $failed = false;

            $rule->validate('selected_event_codes', ['WORKSHOP_1'], function ($message) use (&$failed) {
                $failed = true;
            });

            $this->assertFalse($failed, "Category {$category} should be able to register for workshops");
        }
    }
}
