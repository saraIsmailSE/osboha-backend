<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TimelineTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    
        $timeline_type = ['main', 'profile', 'book', 'group'];
        //TIMELINE TYPE
        $i = 0;
        while ($i <= 3) {
            DB::table('timeline_types')->insert([
                'description'=> "simple desc",
                'type' => $timeline_type[$i],

            ]);
            $i++;
        }
    }
}
