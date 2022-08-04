<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExceptionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $exception_type = ['freeze', 'exams', 'exceptional freeze'];
        //EXCEPTION TYPE
        $i = 0;
        while ($i <= 2) {
            DB::table('exception_types')->insert([

                'type' => $exception_type[$i],

            ]);
            $i++;
        }
    }
}
