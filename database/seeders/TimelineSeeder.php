<?php

namespace Database\Seeders;

use App\Models\Timeline;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TimelineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //main timeline
        Timeline::create([
            'type_id' => 1,
        ]);

        //book timeline
        Timeline::create([
            'type_id' => 3,
        ]);
    }
}