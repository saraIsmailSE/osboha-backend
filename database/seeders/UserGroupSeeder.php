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
        $user= ['Advisor', 'Supervisor', 'Leader', 'Ambassador'];
        $i=0;
        while ($i<=200){
           
            $user = \App\Models\User::factory()->insert([
                'user_id' => rand(1,30),
                'group_id' => rand(1,30),
                'termination_reason' => 'Lorem Ipsum is simply dummy text'
            ]);
            $user->assignRole(rand(0,3));
            $i++;    
        }
    }
} 
