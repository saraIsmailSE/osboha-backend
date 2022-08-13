<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;


class MessagesController extends Controller
{
    use ResponseJson;

    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required',
            'body' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $request['user_id'] = Auth::id();
        Message::create($request->all());
        return $this->jsonResponseWithoutMessage("Message Added Successfully", 'data', 200);
    }
    public function listAllMessages()
    {
        //get and display all the messages
        $messages = DB::table('messages')
            ->where('sender_id', Auth::id())
            ->orWhere('receiver_id', Auth::id())
            ->groupBy('sender_id')
            ->get();

        if ($messages) {
            return $this->jsonResponseWithoutMessage($messages, 'data', 200);
        } else {
            //not found message response
            throw new NotFound;
        }
    }

    public function listMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'partner_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $partner=$request->partner_id;
        //get and display all the messages
        $messages=Message::where(function($q) {
        $q->where('sender_id', Auth::id())
        ->orWhere('receiver_id', Auth::id());
        })
        ->where(function($q) use ($partner){
            $q->where('sender_id',$partner)
            ->orWhere('receiver_id',$partner);
            })->get();

        if ($messages) {
            return $this->jsonResponseWithoutMessage($messages, 'data', 200);
        } else {
            //not found message response
            throw new NotFound;
        }
    }

    public function listRoomMessages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        // check if user belongs to room

        if(Auth::user()->rooms->where('id','==',$request->room_id)->count() > 0){
            $messages = Message::where('room_id', $request->room_id)->get();

            return $this->jsonResponseWithoutMessage($messages, 'data', 200);
            
        }
        else{
            throw new NotAuthorized;
        }

    }
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'partner_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        //get and display all the messages
        $partner=$request->partner_id;
        $messages=Message::where('sender_id', $request->partner_id)
                        ->Where('receiver_id', Auth::id())
                        ->where('status', 0)
                        ->update(['status'=>1]);

        return $this->jsonResponseWithoutMessage("Status Updated Successfully", 'data', 200);
    
    }

}
