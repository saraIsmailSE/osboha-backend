<?php

namespace Database\Seeders;

use App\Models\InfographicSeries;
use Illuminate\Database\Seeder;

class InfographicSeriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        InfographicSeries::create([
            'title' => 'Facebook Changes',
            'section' => 'facebook',
        ]);

        InfographicSeries::create([
            'title' => 'Ramadan',
            'section' => 'section 5',
        ]);

        InfographicSeries::create([
            'title' => 'Eid',
            'section' => 'sextion 6',
        ]);
    }
}
