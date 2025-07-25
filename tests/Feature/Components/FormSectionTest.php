<?php

namespace Tests\Feature\Components;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormSectionTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_basic_form_section()
    {
        $view = $this->blade(
            '<x-form-section title="Basic Section">
                <p>Section content</p>
            </x-form-section>'
        );

        $view->assertSee('Basic Section');
        $view->assertSee('Section content');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_section_with_description()
    {
        $view = $this->blade(
            '<x-form-section title="Section Title" description="Section description">
                <p>Content</p>
            </x-form-section>'
        );

        $view->assertSee('Section Title');
        $view->assertSee('Section description');
        $view->assertSee('Content');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_section_with_step_number()
    {
        $view = $this->blade(
            '<x-form-section title="Step Section" step="1">
                <p>Step content</p>
            </x-form-section>'
        );

        $view->assertSee('Step Section');
        $view->assertSee('1');
        $view->assertSee('Step content');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_completed_section_with_checkmark()
    {
        $view = $this->blade(
            '<x-form-section title="Completed Section" step="1" completed>
                <p>Completed content</p>
            </x-form-section>'
        );

        $view->assertSee('Completed Section');
        $view->assertSee('bg-green-100', false);
        $view->assertSee('text-green-800', false);
        $view->assertSee('Completed content');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_section_with_icon()
    {
        $icon = '<svg class="w-5 h-5"><path d="test-icon-path"/></svg>';

        $view = $this->blade(
            '<x-form-section title="Icon Section" :icon="$icon">
                <p>Icon content</p>
            </x-form-section>',
            compact('icon')
        );

        $view->assertSee('Icon Section');
        $view->assertSee('test-icon-path', false);
        $view->assertSee('Icon content');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_collapsible_section()
    {
        $view = $this->blade(
            '<x-form-section title="Collapsible Section" collapsible>
                <p>Collapsible content</p>
            </x-form-section>'
        );

        $view->assertSee('Collapsible Section');
        $view->assertSee('cursor-pointer', false);
        $view->assertSee('@click="expanded = !expanded"', false);
        $view->assertSee('Collapsible content');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_collapsed_section_by_default()
    {
        $view = $this->blade(
            '<x-form-section title="Collapsed Section" collapsible :expanded="false">
                <p>Hidden content</p>
            </x-form-section>'
        );

        $view->assertSee('Collapsed Section');
        $view->assertSee('expanded: false', false);
        $view->assertSee('Hidden content');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_shows_expand_collapse_icons_for_collapsible_sections()
    {
        $view = $this->blade(
            '<x-form-section title="Toggle Section" collapsible>
                <p>Toggle content</p>
            </x-form-section>'
        );

        $view->assertSee('Toggle Section');
        $view->assertSee('x-show="!expanded"', false);
        $view->assertSee('x-show="expanded"', false);
        $view->assertSee('M19 9l-7 7-7-7', false); // Down arrow path
        $view->assertSee('M5 15l7-7 7 7', false); // Up arrow path
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_alpine_js_transitions()
    {
        $view = $this->blade(
            '<x-form-section title="Transition Section" collapsible>
                <p>Transition content</p>
            </x-form-section>'
        );

        $view->assertSee('x-transition:enter="transition ease-out duration-300"', false);
        $view->assertSee('x-transition:leave="transition ease-in duration-200"', false);
        $view->assertSee('opacity-0 max-h-0', false);
        $view->assertSee('opacity-100 max-h-full', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_section_with_both_step_and_completed_state()
    {
        $view = $this->blade(
            '<x-form-section title="Step Complete" step="2" completed>
                <p>Completed step content</p>
            </x-form-section>'
        );

        $view->assertSee('Step Complete');
        $view->assertSee('bg-green-100', false);
        $view->assertSee('M16.707 5.293a1 1 0 010 1.414l-8 8', false); // Checkmark path
        $view->assertSee('Completed step content');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_completion_checkmark_in_title()
    {
        $view = $this->blade(
            '<x-form-section title="Complete Title" completed>
                <p>Content</p>
            </x-form-section>'
        );

        $view->assertSee('Complete Title');
        $view->assertSee('text-green-600', false);
        $view->assertSee('M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1', false); // Title checkmark path
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_applies_hover_effects_for_collapsible_sections()
    {
        $view = $this->blade(
            '<x-form-section title="Hover Section" collapsible>
                <p>Hover content</p>
            </x-form-section>'
        );

        $view->assertSee('hover:bg-gray-100', false);
        $view->assertSee('dark:hover:bg-gray-600', false);
        $view->assertSee('transition-colors duration-200', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_uses_usp_brand_colors()
    {
        $view = $this->blade(
            '<x-form-section title="Brand Section" step="1">
                <p>Brand content</p>
            </x-form-section>'
        );

        $view->assertSee('bg-usp-blue-pri', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_supports_dark_mode_classes()
    {
        $view = $this->blade(
            '<x-form-section title="Dark Mode Section">
                <p>Dark content</p>
            </x-form-section>'
        );

        $view->assertSee('dark:bg-gray-800', false);
        $view->assertSee('dark:border-gray-700', false);
    }
}
