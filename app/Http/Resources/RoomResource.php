<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            "roomId" => (string) $this->id,
            "roomName" => $this->name,
            "avatar" => $this->avatar,
            "unreadCount" => $this->unreadCount ?? 0,
            "index" => $this->created_at,
            "lastMessage" => $this->lastMessage ?
                new MessageResource($this->lastMessage)
                : null,
            "users" => $this->users,
            "type" => $this->type,
            "messages_status" => $this->messages_status
        ];
    }
}


/**
 * Rooms structure based on vue-advanced-chat
 */
        // rooms="[
        //     {
        //       roomId: '1', 
        //       roomName: 'Room 1',
        //       avatar: 'assets/imgs/people.png',
        //       unreadCount: 4,
        //       index: 3,
        //       lastMessage: {
        //         _id: 'xyz',
        //         content: 'Last message received',
        //         senderId: '1234',
        //         username: 'John Doe',
        //         timestamp: '10:20',
        //         saved: true,
        //         distributed: false,
        //         seen: false,
        //         new: true
        //       },
        //       users: [
        //         {
        //           _id: '1234',
        //           username: 'John Doe',
        //           avatar: 'assets/imgs/doe.png',
        //           status: {
        //             state: 'online',
        //             lastChanged: 'today, 14:30'
        //           }
        //         },
        //         {
        //           _id: '4321',
        //           username: 'John Snow',
        //           avatar: 'assets/imgs/snow.png',
        //           status: {
        //             state: 'offline',
        //             lastChanged: '14 July, 20:00'
        //           }
        //         }
        //       ],
        //       typingUsers: [ 4321 ]
        //     }
        //   ]"