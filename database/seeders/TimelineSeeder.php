<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TimelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $i = 0;
        while ($i <= 200) {
            DB::table('timelines')->insert([
                'name' => Str::random(10),
                'description' => Str::random(50),
                'type_id' => rand(1,4),
            ]);
            $i++;
        }
    }
}
