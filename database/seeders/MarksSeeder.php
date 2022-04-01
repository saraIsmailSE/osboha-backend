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
        Mark::create([
            'user_id' => '1',
            'week_id' => '2',
            'out_of_90' => '90', 
            'out_of_100' => '100', 
            'total_pages' => '30',  
            'support' => '1', 
            'total_thesis' => '0', 
            'total_screenshot' => '5'
        ]);

        Mark::create([
            'user_id' => '2',
            'week_id' => '1',
            'out_of_90' => '54', 
            'out_of_100' => '64', 
            'total_pages' => '18',  
            'support' => '5', 
            'total_thesis' => '0', 
            'total_screenshot' => '3'
        ]);

        Mark::create([
            'user_id' => '3',
            'week_id' => '1',
            'out_of_90' => '72', 
            'out_of_100' => '172', 
            'total_pages' => '24',  
            'support' => '3', 
            'total_thesis' => '1', 
            'total_screenshot' => '3'
        ]);
    }
}
