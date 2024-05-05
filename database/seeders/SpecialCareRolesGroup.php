<?php

namespace Database\Seeders;

use App\Models\GroupType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SpecialCareRolesGroup extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create(['name' => 'special_care_coordinator']);
        Role::create(['name' => 'special_care_leader']);

        //GROUP TYPE
        $group_type = [
            ['type' => 'special_care', 'created_at' => now(), 'updated_at' => now()],
        ];
        GroupType::insert($group_type);
    }
}
