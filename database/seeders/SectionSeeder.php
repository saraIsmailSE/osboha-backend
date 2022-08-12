<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $section = ['علمي', 'تاريخي', 'ديني', 'سياسي', 'انجليزي', 'ثقافي', 'تربوي', 'تنمية'];
        $i = 0;
        //SECTIONS
        while ($i <= 7) {
            DB::table('sections')->insert([

                'section' => $section[$i],

            ]);
            $i++;
        }
    }
}