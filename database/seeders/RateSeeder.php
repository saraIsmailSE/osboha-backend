<?php

namespace Database\Seeders;

use App\Models\Rate;
use Illuminate\Database\Seeder;

class RateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for($i=0; $i<200; $i++){	
            Rate::create([
                'comment_id' => rand(0, 200) ?: null,
                'user_id' => rand(1, 200),
                'post_id' => rand(0, 200) ?: null,
                'rate' => rand(1, 5)
            ]);
        }
    }
}
