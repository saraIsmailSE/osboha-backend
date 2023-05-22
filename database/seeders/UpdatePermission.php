<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UpdatePermission extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $leaderRole = Role::findByName('leader');
        $leaderRole->givePermissionTo('audit mark');

        $supervisorRole = Role::findByName('supervisor');
        $supervisorRole->givePermissionTo('audit mark');

        $advisorRole = Role::findByName('advisor');
        $advisorRole->givePermissionTo('audit mark');
    }
}