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

    /**
     * Add a new room to the system (“create room” permission is required)
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
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

        if(Auth::user()->can('room control'))
        {
            $user = User::find($request->user_id);
            $participant = new Participant([
                'user_id' => $request->user_id,
                'room_id' => $request->room_id,
                'type' => ''
            ]);

            if(User::find($request->user_id)){
                if(Participant::where('user_id', $request->user_id)->get()->isNotEmpty()){

                    return $this->jsonResponseWithoutMessage("This User already in Room", 'data', 500); 
                }
                else{
                    //added user to participant table
                    $user->participant()->save($participant);

                    return $this->jsonResponseWithoutMessage("User Added Successfully", 'data', 200); 
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
