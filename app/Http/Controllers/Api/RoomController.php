<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\RoomResource;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    use ResponseJson;

    public function index() {}

    /**
     * Add a new room to the system (“create room” permission is required)
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'name' => 'required',
            // 'type' => 'required',
            // 'messages_status' => 'required',
            'receiver_id' => "exists:users,id|required"
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors()->first(), 'data', 500);
        }

        $room = Room::create([
            "name" => Str::random(10),
            "creator_id" => Auth::id(),
            "type" => "private",
        ]);

        //attach participants
        $room->users()->attach(Auth::id(), ['type' => 'user']);
        $room->users()->attach($request->receiver_id, ['type' => 'user']);

        $room->fresh();
        return $this->jsonResponseWithoutMessage(new RoomResource($room), 'data', 200);
    }

    public function show(Request $request)
    {
        //
    }

    public function update(Request $request)
    {
        //
    }

    public function delete(Request $request)
    {
        //
    }

    /**
     * Add a new user to the room(“room control” permission is required)
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function addUserToRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'room_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->can('room control')) {
            $user = User::find($request->user_id);
            $participant = new Participant([
                'user_id' => $request->user_id,
                'room_id' => $request->room_id,
                'type' => ''
            ]);

            if (User::find($request->user_id)) {
                if (Participant::where('user_id', $request->user_id)->get()->isNotEmpty()) {

                    return $this->jsonResponseWithoutMessage("This User already in Room", 'data', 500);
                } else {
                    //added user to participant table
                    $user->participant()->save($participant);

                    return $this->jsonResponseWithoutMessage("User Added Successfully", 'data', 200);
                }
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * list rooms for specific user
     * @author Asmaa
     * 
     * @return json
     */
    public function listRooms()
    {
        $user_id = Auth::id();

        $rooms = Room::where("type", "private")
            ->whereHas('users', function ($q) use ($user_id) {
                $q->where('user_id', $user_id)
                    ->groupBy('room_id');
            })
            ->orderBy('created_at', 'desc')
            // ->paginate(4);
            ->get();

        // $total = $rooms->total();
        // $lastPage  = $rooms->lastPage();

        if ($rooms->isEmpty()) {
            return $this->jsonResponseWithoutMessage([], 'data', 200);
        }

        return $this->jsonResponseWithoutMessage(
            [
                "rooms" => RoomResource::collection($rooms),
                // 'total' => $total,
                // 'last_page' => $lastPage,
            ],
            'data',
            200
        );
    }
}
