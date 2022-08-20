<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use App\Models\Message;
use App\Models\User;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //Room Type could be['group', 'workgroup', 'management'];

        ######## Seedind Group Rooms #######

        $rooms = room::factory(10)->create([
            'type' => 'group',
            'creator_id' => 1
        ]);

        ######## End Seedind Group Rooms #######

        
        for($i = 1; $i <= 10; $i++){

        $users = User::inRandomOrder()->limit(10)->get();
        
        $room = Room::where('id',$i)->first();
                
        foreach( $users as $user){
            $room->users()->attach($user->id, ['type' =>'group']);
        }
        

        Message::factory(rand(1, 20))->create([
            'sender_id' => $users[rand(0,9)]->id,
            'status' => 0,
            'room_id' => $room->id
        ]);
        }
                    
    }
}