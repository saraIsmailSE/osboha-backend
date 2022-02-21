<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reaction;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
class ReactionController extends Controller
{
    use ResponseJson;

    public function index()
    {
        $reactions = Reaction::all();
        if($reactions){
            return $this->jsonResponseWithoutMessage($reactions, 'data',200);
        }
        else{
           // throw new NotFound;
        }
    }
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'media_id' => 'required',
            'comment_id' => 'required_without:post_id',
            'post_id' => 'required_without:comment_id',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    
        Reaction::create($request->all());
        return $this->jsonResponseWithoutMessage("Reaction Craeted Successfully", 'data', 200);
       
    }
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required_without:post_id',
            'post_id' => 'required_without:comment_id',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if($request->has('comment_id'))
         $reaction = Reaction::where('comment_id', $request->comment_id)->get();
        else if($request->has('post_id'))
         $reaction = Reaction::where('post_id', $request->post_id)->get();
        if($reaction){
            return $this->jsonResponseWithoutMessage($reaction, 'data',200);
        }
        else{
           // throw new NotFound;
        }
    }
    
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'media_id' => 'required',
            'comment_id' => 'required_without:post_id',
            'post_id' => 'required_without:comment_id',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if($request->has('comment_id'))
         $reaction = Reaction::where('user_id', Auth::user()->id)->where('comment_id', $request->comment_id)->first();
        else if($request->has('post_id'))
         $reaction = Reaction::where('user_id', Auth::user()->id)->where('post_id', $request->post_id)->first();
        if($reaction){
            $reaction->update($request->all());
            return $this->jsonResponseWithoutMessage("Reaction Updated Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;   
        }
    }
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required_without:post_id',
            'post_id' => 'required_without:comment_id',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        if($request->has('comment_id'))
         $reaction = Reaction::where('user_id', Auth::user()->id)->where('comment_id', $request->comment_id)->first();
        else if($request->has('post_id'))
         $reaction = Reaction::where('user_id', Auth::user()->id)->where('post_id', $request->post_id)->first();

        if($reaction){
            $reaction->delete();
            return $this->jsonResponseWithoutMessage("Reaction Deleted Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;
        }
    }
}
