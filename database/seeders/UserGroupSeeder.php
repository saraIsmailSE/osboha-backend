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
           ######################CANCELED#######################
        //     $userGroup = \App\Models\UserGroup::factory()->insert([
        //         'user_id' => rand(1,30),
        //         'group_id' => rand(1,30),
        //         'user_type' => $user_type[rand(0,3)]
        //     ]);
        //     $userGroup->assignRole($user_type);
        //     $i++;    
        // }
    }
} 
