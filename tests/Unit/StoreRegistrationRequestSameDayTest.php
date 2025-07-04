<?php

namespace Tests\Unit;

use App\Http\Requests\StoreRegistrationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreRegistrationRequestSameDayTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that StoreRegistrationRequest allows same day departure
     */
    public function test_store_registration_request_accepts_same_day_departure(): void
    {
        $data = [
            'arrival_date' => '2025-09-28',
            'departure_date' => '2025-09-28', // Same day as arrival
        ];

        $request = new StoreRegistrationRequest();
        $rules = $request->rules();

        // Extract only the rules we need for this test
        $testRules = [
            'arrival_date' => $rules['arrival_date'],
            'departure_date' => $rules['departure_date'],
        ];

        $validator = Validator::make($data, $testRules);

        $this->assertFalse($validator->fails());
        $this->assertEmpty($validator->errors()->all());
    }

    /**
     * Test that StoreRegistrationRequest rejects departure before arrival
     */
    public function test_store_registration_request_rejects_departure_before_arrival(): void
    {
        $data = [
            'arrival_date' => '2025-09-28',
            'departure_date' => '2025-09-27', // Before arrival
        ];

        $request = new StoreRegistrationRequest();
        $rules = $request->rules();

        // Extract only the rules we need for this test
        $testRules = [
            'arrival_date' => $rules['arrival_date'],
            'departure_date' => $rules['departure_date'],
        ];

        $validator = Validator::make($data, $testRules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('departure_date', $validator->errors()->toArray());
    }
}