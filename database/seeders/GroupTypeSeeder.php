<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $group_type = ['reading', 'working', 'supervising'];
        //GROUP TYPE
        $i = 0;
        while ($i <= 2) {
            DB::table('group_types')->insert([

                'type' => $group_type[$i],

            ]);
            $i++;
        }
    }
}