<?php

namespace Database\Seeders;

use App\Models\Thesis;
use Illuminate\Database\Seeder;

class ThesisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i=0; $i<200; $i++){
            $timestamp = mt_rand(1, time());
            $arr = [date("Y-m-d H:i:s", $timestamp), 0];
            $type= ['noraml', 'ramadan', 'young', 'kids'];
            Thesis::create([
                'comment_id' => rand(1, 200),
                'user_id' => rand(1, 200),
                'max_length' => rand(0, 1000),
                'book_id' => rand(1, 200),
                'type' => $type[rand(0,3)],
                'mark_id' => rand(1, 2000),
                'total_pages' => rand(0, 100),
                'total_screenshots' => rand(0, 5),
                'is_acceptable' => array_rand($arr) ? date("Y-m-d H:i:s", $timestamp) : null,
            ]);
        }
    }
}
