<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Media;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\CommentResource;


class CommentController extends Controller
{
    use ResponseJson , MediaTraits;

    public function create(Request $request){

        $validator = Validator::make($request->all(), [
            'body' => 'required',
            'post_id' => 'required_without_all:comment_id',
            'comment_id' => 'required_without_all:post_id',
            'type' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $input=$request->all();
        $input['user_id']= Auth::id();
        $comment = Comment::create($input);
        if ($request->hasFile('image')) {
            // if comment has media
            // upload media
            $this->createMedia($request->file('image'), $comment->id, 'comment');
        }
        return $this->jsonResponseWithoutMessage("Comment Craeted Successfully", 'data', 200);  

    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        $comment = Comment::find($request->comment_id);
        if($comment){
            return $this->jsonResponseWithoutMessage(new CommentResource($comment), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required',
            'user_id' => 'required',
            'comment_id' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $comment = Comment::find($request->comment_id);
        if($comment){
            if(Auth::id() == $comment->user_id){
                if($request->hasFile('image')){
                    // if comment has media
                    //check Media
                    $currentMedia= Media::where('comment_id', $comment->id)->first();
                    // if exists, update
                    if($currentMedia){
                        $this->updateMedia($request->file('image'), $currentMedia->id);
                    }
                    //else create new one
                    else {
                        // upload media
                        $this->createMedia($request->file('image'), $comment->id, 'comment');
                    }
                } else {
                    $comment->update($request->all());
                }
                return $this->jsonResponseWithoutMessage("Comment Updated Successfully", 'data', 200);
            }         
            else{
                throw new NotAuthorized;   
            }
        }
        else{
            throw new NotFound;   
        }
            
        
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        $comment = Comment::find($request->comment_id);
        if($comment){
            if(Auth::user()->can('delete comment') || Auth::id() == $comment->user_id){
                //check Media
                $currentMedia = Media::where('comment_id', $comment->id)->first();
                // if exist, delete
                if ($currentMedia) {
                    $this->deleteMedia($currentMedia->id);
                }
                $comment->delete();
                return $this->jsonResponseWithoutMessage("Comment Deleted Successfully", 'data', 200);
            }
            
            else{
                throw new NotAuthorized;   
            }
        }
        else{
            throw new NotFound;
        }
        
        
    }
}
