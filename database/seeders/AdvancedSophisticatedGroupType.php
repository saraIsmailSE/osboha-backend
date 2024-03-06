<?php

namespace Database\Seeders;

use App\Models\GroupType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdvancedSophisticatedGroupType extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //GROUP TYPE
        $group_type = [
            ['type' => 'advanced_followup', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'sophisticated_followup', 'created_at' => now(), 'updated_at' => now()],
        ];
        GroupType::insert($group_type);
    }
}
