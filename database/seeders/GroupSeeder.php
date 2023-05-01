<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Group;
use App\Models\Timeline;
use App\Models\TimelineType;
use App\Models\User;
use App\Traits\MediaTraits;

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
        Group::factory(10)->create([
            'type_id' => 1,
        ]);
        ######## End Seed Reading Groups #######

    }
}