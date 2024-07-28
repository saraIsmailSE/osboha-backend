<?php

namespace Database\Seeders;

use App\Models\GroupType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class OsbohaSupportRoles extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create(['name' => 'osboha_support_coordinator']);
        Role::create(['name' => 'osboha_support_member']);
    }
}
