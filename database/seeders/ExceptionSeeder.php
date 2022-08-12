<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExceptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $i=0;
        while ($i<=200){

            DB::table('exceptions')->insert([
                'type_id' => rand(1,3),
            ]);
            $i++;
        }
    }
}