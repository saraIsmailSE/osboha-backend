<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reaction;
use App\Models\Media;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Http\Resources\ReactionResource;

class ReactionController extends Controller
{
    use ResponseJson , MediaTraits;

    public function index()
    {
        $reactions = Reaction::where('user_id', Auth::id())->get();
        if($reactions){
            return $this->jsonResponseWithoutMessage(ReactionResource::collection($reactions), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'reaction_id' => 'required_without_all:media,type',
            'media_id'    => 'required_without_all:media,type',
            'comment_id'  => 'required_without_all:post_id,media,type',
            'post_id'     => 'required_without_all:comment_id,media,type',
            'media'       => 'required_if:reaction_id,==,0',
            'type'        => 'required_if:reaction_id,==,0',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        // if reaction_id != 0 ; user add reaction to post or comment
        if($request['reaction_id'] != 0)
        {
            $input = $request->all();
            $input['user_id']= Auth::id();
            Reaction::create($input);
            return $this->jsonResponseWithoutMessage("Reaction Craeted Successfully", 'data', 200);
        }
        // else if reaction_id == 0 ; user have permission to add new reaction 
        else
        {
            if(Auth::user()->can('create reaction')){
                $input_media = $request->all();
                // upload media
                $input = $this->createMedia($request->file('media'),0,$request['type']);
                $input_reaction['reaction_id']= 0;
                $input_reaction['media_id'] = $input->id;
                $input_reaction['user_id']= Auth::id();
                Reaction::create($input_reaction);
                return $this->jsonResponseWithoutMessage("Media and Reaction Craeted Successfully", 'data', 200);
            }
            else{
                throw new NotAuthorized;   
            }
        }
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
            return $this->jsonResponseWithoutMessage(ReactionResource::collection($reaction), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }
    
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'media_id'   => 'required',
            'comment_id' => 'required_without:post_id',
            'post_id'    => 'required_without:comment_id',
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
            throw new NotFound;;   
        }
    }
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required_without:post_id',
            'post_id'    => 'required_without:comment_id',
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
            throw new NotFound;
        }
    }
    
}
