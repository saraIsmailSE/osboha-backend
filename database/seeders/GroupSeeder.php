<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Group;
use App\Models\Timeline;
use App\Models\TimelineType;
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
        //Group Type could be ['followup','supervising','advising','consultation','Administration']

        ######## Seed Reading Groups #######
        $timeline_type = TimelineType::where('type', 'group')->first()->id;

        for ($i = 0; $i < 10; $i++) {
            $group = Group::factory()->create([
                'type_id' => 1,
                'timeline_id' => Timeline::create(['type_id' => $timeline_type])->id,
            ]);
        }

        ######## End Seed Reading Groups #######

    }
}