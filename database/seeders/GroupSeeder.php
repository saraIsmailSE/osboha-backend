<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GroupSeeder extends Seeder
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

            DB::table('groups')->insert([
                'name' => Str::random(10),
                'description' => Str::random(20),
                'type_id' => rand(1,4),
                'cover_picture' => Str::random(10),
                'creator_id' => rand(1,30),
                'timeline_id' => rand(1,30)
            ]);
            $i++;
        }
    }
}