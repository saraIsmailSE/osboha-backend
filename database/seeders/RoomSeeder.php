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


        $users = User::inRandomOrder()->limit(10)->get();
        
        //$user = User::all()->first();
                
            Room::factory(10)
                ->create(['type' => 'group', 'creator_id' => 1])
                ->each(function ($room) use ($users){
                    $room->users()->attach($users[0], ['type' => 'admin']);
                    $room->users()->attach($users->where('id', '!=' , $users[0]->id), ['type' => 'participant']);
                    
                    Message::factory(rand(1, 20))->create([
                        'sender_id' => $users[rand(0,9)]->id,
                        'receiver_id' => 0,
                        'status' => 0,
                        'room_id' => $room->id
                    ]);
                }); 
    }      

}

