<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GroupTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('group_types')->insert([
                'type' => 'reading',     
            ]);
        DB::table('group_types')->insert([
                'type' => 'leading',           
            ]);
        DB::table('group_types')->insert([     
                'type' => 'advising',      
            ]);
        DB::table('group_types')->insert([     
                'type' => 'supervising',      
            ]);
    }
}
