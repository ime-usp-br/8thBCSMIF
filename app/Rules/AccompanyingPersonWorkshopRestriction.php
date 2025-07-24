<?php

namespace App\Rules;

use App\Models\Event;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class AccompanyingPersonWorkshopRestriction implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * Validates that accompanying_person category cannot register for workshops.
     * This rule checks if the registration category is 'accompanying_person' and
     * if any of the selected events are workshops (non-main conference events).
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Get the registration category from form data
        $registrationCategory = $this->data['registration_category_snapshot'] ?? null;

        if ($registrationCategory !== 'accompanying_person') {
            return; // Rule only applies to accompanying persons
        }

        // If value is not an array of event codes, nothing to validate
        if (! is_array($value)) {
            return;
        }

        // Check if any selected events are workshops (non-main conference)
        $workshopEvents = Event::whereIn('code', $value)
            ->where('is_main_conference', false)
            ->get();

        if ($workshopEvents->isNotEmpty()) {
            $workshopNames = $workshopEvents->pluck('name')->join(', ');
            $fail(__('Accompanying persons cannot register for workshops. The following events are workshops: :workshops', [
                'workshops' => $workshopNames,
            ]));
        }
    }
}
