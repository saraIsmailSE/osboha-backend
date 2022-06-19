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
use App\Models\Participant;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoomController extends Controller
{
    use ResponseJson;
   
    public function index()
    {

    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type' => 'required',
            'messages_status' =>'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        } 
        
        if(Auth::user()->can('create room')){
            $input=$request->all();
            $input['creator_id']= Auth::id();
            Room::create($input);
            return $this->jsonResponseWithoutMessage("Room Craeted Successfully", 'data', 200);
        }
        else{
           throw new NotAuthorized;   
        }
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

    public function addUserToRoom(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if(Auth::user()->can('room control'))
        {
            $input = $request->all();
            $user = User::find($input['id']);
            $room = Room::where('creator_id', Auth::id())->get()[0];
            $participant = new Participant([
                'user_id' => $user->id,
                'room_id' => $room->id,
                'type' => ''
            ]);

            if($user){
                if(!(Participant::where('user_id', $user->id)->get())){
                    //added user to participant table
                    $user->participant()->save($participant);

                    return $this->jsonResponseWithoutMessage("User Added Successfully", 'data', 200);
                }
                else{
                    return $this->jsonResponseWithoutMessage("This User already in Room", 'data', 500);
                }
            }
            else{
                throw new NotFound;
            }
        } 
        else{
           throw new NotAuthorized;   
        }
    }
}
