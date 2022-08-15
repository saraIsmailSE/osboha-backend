<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Group;
use App\Models\Timeline;
use App\Models\User;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Group Type could be['reading', 'working', 'supervising'];

        ######## Seed Reading Groups #######
        $group = 0;
        while ($group <= 9) {

            $timeline = Timeline::create(['type_id' => 4]);
            group::factory(1)->create([
                'type_id' => 1,
                'timeline_id' => $timeline->id
            ]);
            $group++;
        }
        ######## End Seed Reading Groups #######

    }
}