<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $section= ['علمي', 'تاريخي', 'ديني', 'سياسي' , 'انجليزي' , 'ثقافي' ,'تربوي' ,'تنمية'];
        $type= ['noraml', 'ramadan', 'young', 'kids'];
        $level= ['بسيط', 'متوسط', 'عميق'];
       $i=0;
        while ($i<=200){
            
            DB::table('books')->insert([
                'post_id' => rand(0,15),
                'name' => Str::random(10),
                'writer' => Str::random(10),
                'publisher' => Str::random(10),
                'brief' => Str::random(150),
                'start_page' => 1,
                'end_page' => rand(150,600),
                'link' => 'https://www.google.com/',
                'section' => $section[rand(0,7)],
                'type' => $type[rand(0,3)],
                'level' => $level[rand(0,2)],
            ]);
            $i++;    
        }
    }
}
