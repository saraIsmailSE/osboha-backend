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

        $type= ['noraml', 'book', 'article', 'infographic', 'support', 'discussion'];
       $i=0;
        while ($i<=200){
            
            DB::table('posts')->insert([
                'body' => Str::random(3000),
                'user_id' => rand(1,30),
                'timeline_id' => rand(1,30),
                'type' => $type[rand(0,5)],
            ]);
            $i++;    
        }
    }
}