<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ThesisTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $thesis_type = ['normal', 'ramadan', 'tafseer', 'young', 'kids'];
        //THESIS TYPE
        $i = 0;
        while ($i <= 4) {
            DB::table('thesis_types')->insert([

                'type' => $thesis_type[$i],

            ]);
            $i++;
        }
    }
}