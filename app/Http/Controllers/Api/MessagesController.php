<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MessagesController extends Controller
{
    use ResponseJson;

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'sender_id' => 'required',
            'receiver_id' => 'required',
            'status' => 'required',
            'room_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
            $request['user_id']= Auth::id();    
            Message::create($request->all());
            return $this->jsonResponseWithoutMessage("Message Added Successfully", 'data', 200);
    }  
    public function index()
    {
            //get and display all the messages
            $message = Message::all();
            if($message->isNotEmpty()){
                // found message response
                return $this->jsonResponseWithoutMessage($message, 'data',200);
            }
            else{
                //not found message response
                throw new NotFound;
            }

            
         //get and display messages between 2 users
            $message = Message::with('user')
            ->groupBy('sender_id')
            ->where('receiver_id',auth()->user()->id)
            ->orWhere('sender_id',auth()->user()->id)
           
            ->get();
}
}