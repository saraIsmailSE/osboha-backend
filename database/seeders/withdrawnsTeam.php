<?php

namespace Database\Seeders;

use App\Models\GroupType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class withdrawnsTeam extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create(['name' => 'coordinator_of_withdrawns_team']);
        Role::create(['name' => 'member_of_withdrawns_team']);
    }
}
