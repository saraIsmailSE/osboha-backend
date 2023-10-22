<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class EligibleRoles extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = Role::create(['name' => 'user']);
        $reviewer = Role::create(['name' => 'reviewer']);
        $super_reviewer = Role::create(['name' => 'auditor']);
        $auditer = Role::create(['name' => 'book-quality']);
        $super_auditer = Role::create(['name' => 'user_accept']);
        $super_auditer = Role::create(['name' => 'super_auditer']);
        $super_auditer = Role::create(['name' => 'super_reviewer']);
    }
}
