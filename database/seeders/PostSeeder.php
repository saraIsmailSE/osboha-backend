<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
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
            
            DB::table('posts')->insert([
                'body' => Str::random(3000),
                'user_id' => rand(1,200),
                'timeline_id' => rand(1,200),
                'type_id' => rand(1,6),
            ]);
            $i++;    
        }
    }
}