<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Message;
use App\Models\User;

class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user1 = User::where('id', 1)->first();
        $user2 = User::where('id', 2)->first();

            Message::factory(50)->create([
                'sender_id' => $user1,
                'receiver_id' => $user2,
                'status' => 0,
                'room_id' => 0
            ]);  

            Message::factory(50)->create([
                'sender_id' => $user2,
                'receiver_id' => $user1,
                'status' => 0,
                'room_id' => 0
            ]);  

        //Message::inRandomOrder()->limit(15)->update( ['status' => 1]);

        Message::where('sender_id', $user1->id)->limit(40)->update( ['status' => 1]);
        Message::where('sender_id', $user2->id)->limit(35)->update( ['status' => 1]);
        
    }      

}

