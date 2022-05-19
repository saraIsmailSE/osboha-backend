<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FriendSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $i=0;
        while ($i<=200){

            DB::table('friends')->insert([
                'user_id' => rand(1,30),
                'friend_id' => rand(1,30),
                'status' => rand(0,1)
            ]);
            $i++;
        }
    }
}
