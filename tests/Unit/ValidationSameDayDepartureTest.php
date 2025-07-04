<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class ValidationSameDayDepartureTest extends TestCase
{
    /**
     * Test that departure date can be equal to arrival date using Laravel validation rules
     */
    public function test_departure_date_accepts_same_day_as_arrival_date(): void
    {
        $data = [
            'arrival_date' => '2025-09-28',
            'departure_date' => '2025-09-28', // Same day as arrival
        ];

        $rules = [
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after_or_equal:arrival_date',
        ];

        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test that departure date can be after arrival date
     */
    public function test_departure_date_accepts_later_date_than_arrival(): void
    {
        $data = [
            'arrival_date' => '2025-09-28',
            'departure_date' => '2025-09-30', // After arrival
        ];

        $rules = [
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after_or_equal:arrival_date',
        ];

        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test that departure date cannot be before arrival date
     */
    public function test_departure_date_rejects_earlier_date_than_arrival(): void
    {
        $data = [
            'arrival_date' => '2025-09-28',
            'departure_date' => '2025-09-27', // Before arrival
        ];

        $rules = [
            'arrival_date' => 'required|date',
            'departure_date' => 'required|date|after_or_equal:arrival_date',
        ];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('departure_date', $validator->errors()->toArray());
    }
}