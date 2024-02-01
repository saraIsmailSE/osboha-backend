<?php

namespace Database\Seeders;

use App\Models\GroupType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class MarathonRolesGroup extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::create(['name' => 'marathon_coordinator']);
        Role::create(['name' => 'marathon_verification_supervisor']);
        Role::create(['name' => 'marathon_supervisor']);
        Role::create(['name' => 'marathon_ambassador']);
        
        //GROUP TYPE    
        $group_type = [
            ['type' => 'marathon', 'created_at' => now(), 'updated_at' => now()],
        ];
        GroupType::insert($group_type);
    }
}
