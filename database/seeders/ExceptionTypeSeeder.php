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
        $exception_type = ['تجميد الأسبوع الحالي', 'تجميد الأسبوع القادم', 'نظام امتحانات - شهري' ,'نظام امتحانات - فصلي' , 'تجميد استثنائي'];
        //EXCEPTION TYPE
        $i = 0;
        while ($i <= 4) {
            DB::table('exception_types')->insert([

                'type' => $exception_type[$i],

            ]);
            $i++;
        }
    }
}
