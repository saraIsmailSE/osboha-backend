<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
<<<<<<< HEAD
=======
use Illuminate\Support\Str;
>>>>>>> f8263cc8d84c69b7cd7445f682b3fe4492efe3ed

class GroupTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
<<<<<<< HEAD
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
=======

        $group_type = ['reading', 'working', 'supervising'];
        //GROUP TYPE
        $i = 0;
        while ($i <= 2) {
            DB::table('group_types')->insert([

                'type' => $group_type[$i],

            ]);
            $i++;
        }
>>>>>>> f8263cc8d84c69b7cd7445f682b3fe4492efe3ed
    }
}
