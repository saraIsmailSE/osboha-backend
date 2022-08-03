<?php

namespace Database\Seeders;

use App\Models\RejectedTheses;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RejectesThesesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i=0; $i<200; $i++){
            for($j=0; $j<10; $j++){
                $timestamp = mt_rand(1, time());
                $arr = [date("Y-m-d H:i:s", $timestamp), 0];
                RejectedTheses::create([
                    'rejecter_note' => Str::random(15), 
                    'is_acceptable' => array_rand($arr) ? date("Y-m-d H:i:s", $timestamp) : null,
                    'user_id' => rand(1, 200),
                    'thesis_id' => rand(1, 200), 
                    'week_id' => $i+1, 
                    'rejecter_id' => rand(1, 200)
                ]);
            }
        }
    }
}
