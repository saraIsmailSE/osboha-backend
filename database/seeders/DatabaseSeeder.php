<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        //$this->call(PermissionsSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(FreindSeeder::class);
        $this->call(GroupSeeder::class);
        $this->call(UserGroupSeeder::class);
    }
}