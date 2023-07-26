<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\MessageResource;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use App\Traits\MediaTraits;
use Illuminate\Support\Str;

class MessagesController extends Controller
{
    use ResponseJson, MediaTraits;

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "receiver_id" => "exists:users,id|required_without:room_id",
            "room_id" => "exists:rooms,id|required_without:receiver_id",
            "message_id" => "exists:messages,id|nullable",
            'body' => 'required_without:media',
            "media" => "required_without:body|array|max:10",
            "media.*" => "mimes:jpg,jpeg,png,gif,mp4,mov,ogg,qt|max:20000",
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $request['sender_id'] = Auth::id();
        if ($request->has('room_id')) {
            $room = Room::find($request->room_id);
            if (!$room) {
                throw new NotFound;
            }
            if (!$room->users->contains(Auth::id())) {
                throw new NotAuthorized;
            }

            $request['room_id'] = $room->id;
            $request['receiver_id'] = $room->users->where('id', '!=', Auth::id())->first()->id;
        } else {
            $room = Room::where('type', 'private')
                ->whereHas("users", function ($q) use ($request) {
                    $q->where('user_id', Auth::id());
                })
                ->whereHas("users", function ($q) use ($request) {
                    $q->where('user_id', $request->receiver_id);
                })
                ->first();

            if (!$room) {
                $room = Room::create([
                    "name" => Str::random(10),
                    "creator_id" => Auth::id(),
                    "type" => "private",
                ]);

                //attach participants
                $room->users()->attach(Auth::id(), ['type' => 'user']);
                $room->users()->attach($request->receiver_id, ['type' => 'user']);
            }

            $request['room_id'] = $room->id;
        }

        $message  = Message::create($request->only(["sender_id", "receiver_id", "body", "room_id", "message_id"]));

        if ($request->has("media")) {
            foreach ($request->media as $media) {
                $this->createMedia($media, $message->id, 'message', 'messages/' . $room->id);
            }
        }

        return $this->jsonResponseWithoutMessage(
            [
                "message" =>
                new MessageResource($message),
                "room" => new RoomResource($room)
            ],
            'data',
            200
        );
    }

    public function listRoomMessages($room_id)
    {
        $messages = Message::where("room_id", $room_id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        if ($messages) {
            return $this->jsonResponseWithoutMessage([
                "messages" => MessageResource::collection($messages),
                "total" => $messages->total(),
                "last_page" => $messages->lastPage(),
            ], 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage([
                "messages" => [],
                "total" => 0,
                "last_page" => 1
            ], "data", 200);
        }
    }

    public function setMessagesAsRead($room_id)
    {
        $messages =
            Message::where('room_id', $room_id)
            ->Where('receiver_id', Auth::id())
            ->where('status', 0)
            ->update(["status" => 1]);


        if (!$messages) {
            return $this->jsonResponseWithoutMessage("No unread messages", 'data', 200);
        }

        return $this->jsonResponseWithoutMessage("Status Updated Successfully", 'data', 200);
    }

    public function deleteMessage($message_id)
    {
        $message = Message::find($message_id);
        if (!$message) {
            throw new NotFound;
        }

        if ($message->sender_id != Auth::id()) {
            throw new NotAuthorized;
        }

        $message->delete();

        return $this->jsonResponseWithoutMessage("Message Deleted Successfully", 'data', 200);
    }
}
