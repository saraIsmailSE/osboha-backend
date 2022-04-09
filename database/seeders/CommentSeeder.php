<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $type= ['noraml', 'thesis', 'support'];
       $i=0;
        while ($i<=200){
            
            DB::table('comments')->insert([
                'body' => Str::random(3000),
                'user_id' => rand(1,30),
                'post_id' => rand(1,30),
                'comment_id' => rand(0,10),
                'type' => $type[rand(0,2)],
            ]);
            $i++;    
        }
    }
}
