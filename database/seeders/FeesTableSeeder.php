<?php

namespace Database\Seeders;

use App\Models\Fee;
use Illuminate\Database\Seeder;

class FeesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Populates the fees table with all combinations of fees for events
     * based on participant category, type, period, and discounts.
     */
    public function run(): void
    {
        $feesData = [
            // 8th BCSMIF (BCSMIF2025)
            // Undergraduate Student
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'undergrad_student', 'type' => 'in-person', 'period' => 'early', 'price' => 0.00, 'is_discount_for_main_event_participant' => false],
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'undergrad_student', 'type' => 'in-person', 'period' => 'late', 'price' => 0.00, 'is_discount_for_main_event_participant' => false],
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'undergrad_student', 'type' => 'online', 'period' => 'early', 'price' => 0.00, 'is_discount_for_main_event_participant' => false],
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'undergrad_student', 'type' => 'online', 'period' => 'late', 'price' => 0.00, 'is_discount_for_main_event_participant' => false],

            // Graduate Student
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'grad_student', 'type' => 'in-person', 'period' => 'early', 'price' => 600.00, 'is_discount_for_main_event_participant' => false],
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'grad_student', 'type' => 'in-person', 'period' => 'late', 'price' => 700.00, 'is_discount_for_main_event_participant' => false],
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'grad_student', 'type' => 'online', 'period' => 'early', 'price' => 200.00, 'is_discount_for_main_event_participant' => false],
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'grad_student', 'type' => 'online', 'period' => 'late', 'price' => 200.00, 'is_discount_for_main_event_participant' => false],

            // Professor - ABE member
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'professor_abe', 'type' => 'in-person', 'period' => 'early', 'price' => 1200.00, 'is_discount_for_main_event_participant' => false],
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'professor_abe', 'type' => 'in-person', 'period' => 'late', 'price' => 1400.00, 'is_discount_for_main_event_participant' => false],
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'professor_abe', 'type' => 'online', 'period' => 'early', 'price' => 400.00, 'is_discount_for_main_event_participant' => false],
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'professor_abe', 'type' => 'online', 'period' => 'late', 'price' => 400.00, 'is_discount_for_main_event_participant' => false],

            // Professor - ABE non-member / Professional
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'professor_non_abe_professional', 'type' => 'in-person', 'period' => 'early', 'price' => 1600.00, 'is_discount_for_main_event_participant' => false],
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'professor_non_abe_professional', 'type' => 'in-person', 'period' => 'late', 'price' => 2000.00, 'is_discount_for_main_event_participant' => false],
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'professor_non_abe_professional', 'type' => 'online', 'period' => 'early', 'price' => 800.00, 'is_discount_for_main_event_participant' => false],
            ['event_code' => 'BCSMIF2025', 'participant_category' => 'professor_non_abe_professional', 'type' => 'online', 'period' => 'late', 'price' => 800.00, 'is_discount_for_main_event_participant' => false],
        ];

        $workshopEvents = ['RAA2025', 'WDA2025'];
        $workshopFeesData = [
            // Undergraduate Student (Workshop)
            ['participant_category' => 'undergrad_student', 'type' => 'in-person', 'period' => 'early', 'price_discount' => 0.00, 'price_normal' => 0.00],
            ['participant_category' => 'undergrad_student', 'type' => 'in-person', 'period' => 'late', 'price_discount' => 0.00, 'price_normal' => 0.00],
            ['participant_category' => 'undergrad_student', 'type' => 'online', 'period' => 'early', 'price_discount' => 0.00, 'price_normal' => 0.00],
            ['participant_category' => 'undergrad_student', 'type' => 'online', 'period' => 'late', 'price_discount' => 0.00, 'price_normal' => 0.00],

            // Graduate Student (Workshop)
            ['participant_category' => 'grad_student', 'type' => 'in-person', 'period' => 'early', 'price_discount' => 0.00, 'price_normal' => 0.00],
            ['participant_category' => 'grad_student', 'type' => 'in-person', 'period' => 'late', 'price_discount' => 0.00, 'price_normal' => 0.00],
            ['participant_category' => 'grad_student', 'type' => 'online', 'period' => 'early', 'price_discount' => 0.00, 'price_normal' => 0.00],
            ['participant_category' => 'grad_student', 'type' => 'online', 'period' => 'late', 'price_discount' => 0.00, 'price_normal' => 0.00],

            // Professor - ABE member (Workshop)
            ['participant_category' => 'professor_abe', 'type' => 'in-person', 'period' => 'early', 'price_discount' => 100.00, 'price_normal' => 250.00],
            ['participant_category' => 'professor_abe', 'type' => 'in-person', 'period' => 'late', 'price_discount' => 200.00, 'price_normal' => 350.00],
            ['participant_category' => 'professor_abe', 'type' => 'online', 'period' => 'early', 'price_discount' => 100.00, 'price_normal' => 150.00],
            ['participant_category' => 'professor_abe', 'type' => 'online', 'period' => 'late', 'price_discount' => 100.00, 'price_normal' => 150.00],

            // Professor - ABE non-member / Professional (Workshop)
            ['participant_category' => 'professor_non_abe_professional', 'type' => 'in-person', 'period' => 'early', 'price_discount' => 500.00, 'price_normal' => 700.00],
            ['participant_category' => 'professor_non_abe_professional', 'type' => 'in-person', 'period' => 'late', 'price_discount' => 650.00, 'price_normal' => 850.00],
            ['participant_category' => 'professor_non_abe_professional', 'type' => 'online', 'period' => 'early', 'price_discount' => 200.00, 'price_normal' => 350.00],
            ['participant_category' => 'professor_non_abe_professional', 'type' => 'online', 'period' => 'late', 'price_discount' => 200.00, 'price_normal' => 350.00],
        ];

        foreach ($workshopEvents as $eventCode) {
            foreach ($workshopFeesData as $fee) {
                // Add discounted fee
                $feesData[] = [
                    'event_code' => $eventCode,
                    'participant_category' => $fee['participant_category'],
                    'type' => $fee['type'],
                    'period' => $fee['period'],
                    'price' => $fee['price_discount'],
                    'is_discount_for_main_event_participant' => true,
                ];
                // Add normal fee
                $feesData[] = [
                    'event_code' => $eventCode,
                    'participant_category' => $fee['participant_category'],
                    'type' => $fee['type'],
                    'period' => $fee['period'],
                    'price' => $fee['price_normal'],
                    'is_discount_for_main_event_participant' => false,
                ];
            }
        }

        foreach ($feesData as $feeData) {
            Fee::updateOrCreate(
                [
                    'event_code' => $feeData['event_code'],
                    'participant_category' => $feeData['participant_category'],
                    'type' => $feeData['type'],
                    'period' => $feeData['period'],
                    'is_discount_for_main_event_participant' => $feeData['is_discount_for_main_event_participant'],
                ],
                ['price' => $feeData['price']]
            );
        }
    }
}
