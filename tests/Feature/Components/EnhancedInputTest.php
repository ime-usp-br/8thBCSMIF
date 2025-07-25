<?php

namespace Tests\Feature\Components;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnhancedInputTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_basic_text_input()
    {
        $view = $this->blade(
            '<x-enhanced-input name="test_field" label="Test Field" placeholder="Enter text" />'
        );

        $view->assertSee('Test Field');
        $view->assertSee('Enter text');
        $view->assertSeeInOrder(['input', 'type="text"', 'name="test_field"']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_required_field_with_asterisk()
    {
        $view = $this->blade(
            '<x-enhanced-input name="required_field" label="Required Field" required />'
        );

        $view->assertSee('Required Field');
        $view->assertSee('*', false);
        $view->assertSeeInOrder(['input', 'required']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_select_input_with_options()
    {
        $options = ['value1' => 'Option 1', 'value2' => 'Option 2'];

        $view = $this->blade(
            '<x-enhanced-input type="select" name="select_field" label="Select Field" :options="$options" />',
            compact('options')
        );

        $view->assertSee('Select Field');
        $view->assertSeeInOrder(['select', 'name="select_field"']);
        $view->assertSee('Option 1');
        $view->assertSee('Option 2');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_textarea_with_rows()
    {
        $view = $this->blade(
            '<x-enhanced-input type="textarea" name="description" label="Description" rows="5" />'
        );

        $view->assertSee('Description');
        $view->assertSeeInOrder(['textarea', 'name="description"', 'rows="5"']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_help_text()
    {
        $view = $this->blade(
            '<x-enhanced-input name="field" label="Field" help="This is help text" />'
        );

        $view->assertSee('Field');
        $view->assertSee('This is help text');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_with_prefix_and_suffix()
    {
        $view = $this->blade(
            '<x-enhanced-input name="price" label="Price" prefix="R$" suffix=".00" />'
        );

        $view->assertSee('Price');
        $view->assertSee('R$');
        $view->assertSee('.00');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_with_icon()
    {
        $icon = '<svg class="w-5 h-5"><path d="test-path"/></svg>';

        $view = $this->blade(
            '<x-enhanced-input name="field" label="Field" :icon="$icon" />',
            compact('icon')
        );

        $view->assertSee('Field');
        $view->assertSee('test-path', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_disabled_state()
    {
        $view = $this->blade(
            '<x-enhanced-input name="disabled_field" label="Disabled Field" disabled />'
        );

        $view->assertSee('Disabled Field');
        $view->assertSeeInOrder(['input', 'disabled']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_includes_alpine_js_functionality()
    {
        $view = $this->blade(
            '<x-enhanced-input name="test_field" label="Test Field" />'
        );

        $view->assertSee('x-data="enhancedInput', false);
        $view->assertSee('function enhancedInput(config)', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_wire_model_attributes()
    {
        $view = $this->blade(
            '<x-enhanced-input name="wire_field" label="Wire Field" wire:model.blur="test" />'
        );

        $view->assertSee('Wire Field');
        $view->assertSee('wire:model.blur="test"', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_validation_icons_for_wire_model()
    {
        $view = $this->blade(
            '<x-enhanced-input name="validated_field" label="Validated Field" wire:model="test" validation />'
        );

        $view->assertSee('Validated Field');
        $view->assertSee('wire:loading', false);
        $view->assertSee('validationState === \'success\'', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_character_count_for_textarea_with_maxlength()
    {
        $view = $this->blade(
            '<x-enhanced-input type="textarea" name="limited_text" label="Limited Text" maxlength="100" wire:model="text" />'
        );

        $view->assertSee('Limited Text');
        $view->assertSee('100', false);
        $view->assertSee('x-text="count + \' / 100\'"', false);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_non_required_select_with_default_option()
    {
        $options = ['value1' => 'Option 1', 'value2' => 'Option 2'];

        $view = $this->blade(
            '<x-enhanced-input type="select" name="optional_select" :options="$options" />',
            compact('options')
        );

        $view->assertSee('Select an option');
        $view->assertSee('Option 1');
        $view->assertSee('Option 2');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_does_not_show_default_option_for_required_select()
    {
        $options = ['value1' => 'Option 1', 'value2' => 'Option 2'];

        $view = $this->blade(
            '<x-enhanced-input type="select" name="required_select" :options="$options" required />',
            compact('options')
        );

        $view->assertDontSee('Select an option');
        $view->assertSee('Option 1');
        $view->assertSee('Option 2');
    }
}
