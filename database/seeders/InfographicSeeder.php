<?php

namespace Database\Seeders;

use App\Models\Infographic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InfographicSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i=0; $i<200; $i++){
            Infographic::create([
                'title' => Str::random(15),
                'designer_id' => rand(1, 200),
                'section_id' =>rand(1,7),
                'series_id' => rand(1, 5),
            ]);
        }
    }
}
