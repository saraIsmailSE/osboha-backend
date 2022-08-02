<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $type= ['عادي', 'تفسير', 'يافعين', 'أطفال', 'رمضان'];

        $i=0;
        while ($i<=50){
            
            DB::table('books_types')->insert([
                'level' => $type[rand(0,4)],
            ]);
            $i++;    
        }
    }
}
