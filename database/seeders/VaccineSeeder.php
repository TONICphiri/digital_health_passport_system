<?php

namespace Database\Seeders;

use App\Models\Vaccine;
use Illuminate\Database\Seeder;

/**
 * Vaccines from the national immunisation schedule. The System Administrator
 * can add or change vaccines on the Vaccine catalogue page.
 *
 * The age in days for the first dose is an approximation drawn from the
 * schedule wording. It powers the age based immunisation status, so the
 * values must be confirmed by the programme team before going live.
 */
class VaccineSeeder extends Seeder
{
    public function run(): void
    {
        $vaccines = [
            ['BCG', 'Tuberculosis', 1, null, 'At birth', 0],
            ['Oral Polio Vaccine', 'Poliomyelitis', 4, 28, 'At birth, 6, 10 and 14 weeks', 42],
            ['Pentavalent', 'Diphtheria, tetanus, whooping cough, hepatitis B and Haemophilus influenzae type b', 3, 28, '6, 10 and 14 weeks', 42],
            ['Pneumococcal Conjugate Vaccine', 'Pneumonia and meningitis', 3, 28, '6, 10 and 14 weeks', 42],
            ['Rotavirus', 'Severe diarrhoea caused by rotavirus', 2, 28, '6 and 10 weeks', 42],
            ['Measles Rubella', 'Measles and rubella', 2, 180, '9 and 15 months', 270],
            ['Human Papillomavirus', 'Cervical cancer', 2, 180, 'Girls aged 9 to 14 years', 3285],
            ['Tetanus Diphtheria', 'Tetanus and diphtheria', 5, 28, 'Women of child bearing age', 5475],
            ['COVID-19', 'Coronavirus disease', 2, 56, 'Adults 18 years and above', 6570],
            ['Hepatitis B', 'Hepatitis B', 3, 30, 'Adults at risk', 3650],
        ];

        foreach ($vaccines as [$name, $protects, $doses, $interval, $age, $ageDays]) {
            Vaccine::query()->firstOrCreate(['name' => $name], [
                'protects_against' => $protects,
                'total_doses' => $doses,
                'days_between_doses' => $interval,
                'recommended_age' => $age,
                'recommended_age_days' => $ageDays,
                'is_active' => true,
            ]);
        }
    }
}
