<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class RoomReceiverResource extends JsonResource
{
    private $receiver_id;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function __construct($resource, $receiver_id)
    {
        // Ensure you call the parent constructor
        parent::__construct($resource);
        $this->resource = $resource;

        $this->receiver_id = $receiver_id;
    }

    public function toArray($request)
    {
        $contactedUser = $this->users->where('id', '!=', $this->receiver_id)->first();
        $latestMessage = $this->messages()->latest()->first();
        return [
            "roomId" => (string) $this->id,
            "roomName" => $contactedUser->name,
            "avatar" => asset('assets/images/' . $contactedUser->userProfile->profile_picture),
            "unreadCount" => $this->messages->where("status", 0)->where("receiver_id", $this->receiver_id)->count() ?? 0,
            "index" => $latestMessage ? $latestMessage->created_at : $this->created_at,
            "lastMessage" => $latestMessage ? new MessageResource($latestMessage) : null,
            "users" => RoomUserResource::collection($this->users),
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