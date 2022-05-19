<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UserGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user_type= ['advisor', 'supervisor', 'leader', 'ambassador'];
        
        $i=0;
        while ($i<=200){
            $role = $user_type[rand(0,3)];
            $user_id = rand(1,30);
            $userGroup = \App\Models\UserGroup::insert([
                'user_id' => $user_id,
                'group_id' => rand(1,30),
                'user_type' => $role
            ]);
            $user = \App\Models\User::find($user_id); 
            $user->assignRole($role);
            $i++;    
        }
    }
} 
