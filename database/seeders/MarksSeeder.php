<?php

namespace Database\Seeders;

use App\Models\Mark;
use Illuminate\Database\Seeder;

class MarksSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i=0; $i<200; $i++){
            for($j=0; $j<10; $j++){
                Mark::create([
                    'user_id' => rand(1, 200),
                    'week_id' => $i+1,
                    'out_of_90' => rand(0, 90), 
                    'out_of_100'=> rand(0, 100), 
                    'total_pages'=> rand(0, 100),  
                    'support' => array_rand([0, 10]) ? 10 : 0, 
                    'total_thesis' => rand(0, 5), 
                    'total_screenshot' => rand(0, 5)
                ]);
            }
        }
    }
}
