<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RamadanRoles extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create(['name' => 'ramadan_coordinator']);
        Role::create(['name' => 'ramadan_hadith_corrector']);
        Role::create(['name' => 'ramadan_fiqh_corrector']);
        Role::create(['name' => 'ramadan_tafseer_corrector']);
        Role::create(['name' => 'ramadan_vedio_corrector']);
    }
}
