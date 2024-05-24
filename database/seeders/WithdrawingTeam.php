<?php

namespace Database\Seeders;

use App\Models\GroupType;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class WithdrawingTeam extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create(['name' => 'coordinator_of_withdrawing_team']);
        Role::create(['name' => 'member_of_withdrawing_team']);
    }
}
