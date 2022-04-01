<?php

namespace Database\Seeders;

use App\Models\Infographic;
use Illuminate\Database\Seeder;

class InfographicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Infographic::create([
            'title' => 'Book 1',
            'designer_id' => '1',
            'section' => 'section 1',
            'series_id' => null,
        ]);

        Infographic::create([
            'title' => 'Book 2',
            'designer_id' => '3',
            'section' => 'section 2',
            'series_id' => null,
        ]);

        Infographic::create([
            'title' => 'Facebook Cover',
            'designer_id' => '2',
            'section' => 'section 3',
            'series_id' => '1',
        ]);
    }
}
