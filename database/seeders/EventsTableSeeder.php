<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

class EventsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        Event::updateOrCreate(
            ['code' => 'BCSMIF2025'],
            [
                'name' => '8th Brazilian Conference on Statistical Modeling in Insurance and Finance',
                'description' => 'The Institute of Mathematics and Statistics of the University of São Paulo (IME-USP) is announcing the Eighth Brazilian Conference on Statistical Modeling in Insurance and Finance (8th BCSMIF) to be held from September 28 to October 3, 2025 at the Maresias Beach Hotel in Maresias, SP. The 8th BCSMIF objective is to provide a forum for presenting cutting-edge research on the development, implementation of recent methods in the field of Finance and Insurance, with emphasize on the practical applications of Data Science and Machine Learning. The 8th BCSMIF also seeks to promote discussion and the exchange of ideas between young researchers and senior scientists. Traditionally, the event involves graduate students to facilitate their integration into the academic and scientific environment. All speakers are invited to include relevant examples in their presentations. The 8th BCSMIF is open to academic and non-academic communities, including universities, insurance companies, banks, consulting firms, and government agencies. The conference aims to foster cooperation between professionals and researchers in the field. The official language is English.',
                'start_date' => '2025-09-28',
                'end_date' => '2025-10-03',
                'location' => 'Maresias Beach Hotel, Maresias, SP',
                'registration_deadline_early' => '2025-08-15',
                'registration_deadline_late' => null,
                'is_main_conference' => true,
            ]
        );

        Event::updateOrCreate(
            ['code' => 'RAA2025'],
            [
                'name' => 'Risk Analysis and Applications Workshop',
                'description' => 'Risk Analysis and Applications (September 24+25, 2025) at the Institute of Mathematics and Statistics of the University of São Paulo (IME-USP)',
                'start_date' => '2025-09-24',
                'end_date' => '2025-09-25',
                'location' => 'IME-USP, São Paulo',
                'registration_deadline_early' => '2025-08-15',
                'registration_deadline_late' => null,
                'is_main_conference' => false,
            ]
        );

        Event::updateOrCreate(
            ['code' => 'WDA2025'],
            [
                'name' => 'Dependence Analysis Workshop',
                'description' => 'Dependence Analysis (September 26+27, 2025) at the Institute of Mathematics, Statistics and Scientific Computing of the State University of Campinas (IMECC-UNICAMP)',
                'start_date' => '2025-09-26',
                'end_date' => '2025-09-27',
                'location' => 'IMECC-UNICAMP, Campinas',
                'registration_deadline_early' => '2025-08-15',
                'registration_deadline_late' => null,
                'is_main_conference' => false,
            ]
        );
    }
}
