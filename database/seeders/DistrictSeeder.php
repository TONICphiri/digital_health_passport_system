<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Seeder;

/**
 * The 28 districts of Malawi grouped by region.
 */
class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            'Northern' => ['Chitipa', 'Karonga', 'Likoma', 'Mzimba', 'Nkhata Bay', 'Rumphi'],
            'Central' => ['Dedza', 'Dowa', 'Kasungu', 'Lilongwe', 'Mchinji', 'Nkhotakota', 'Ntcheu', 'Ntchisi', 'Salima'],
            'Southern' => ['Balaka', 'Blantyre', 'Chikwawa', 'Chiradzulu', 'Machinga', 'Mangochi', 'Mulanje', 'Mwanza', 'Neno', 'Nsanje', 'Phalombe', 'Thyolo', 'Zomba'],
        ];

        foreach ($regions as $region => $districts) {
            foreach ($districts as $name) {
                District::query()->firstOrCreate(['name' => $name], ['region' => $region]);
            }
        }
    }
}
