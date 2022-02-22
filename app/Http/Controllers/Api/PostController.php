<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Media;
use App\Models\Timeline;
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
use App\Http\Resources\PostResource;


class PostController extends Controller
{
    use ResponseJson;
   
    public function index()
    {
        if(Auth::user()->can('list posts')){
            $posts = Post::all();
            if($posts){
                return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data',200);
            }
            else{
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;
        }
    }
    
    public function create(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'body' => 'required',
            'user_id' => 'required',
            'type' => 'required',
            'timeline_id' => 'required'
        ]);
     
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        $timeline = Timeline::all();
        if($timeline->type=="group"){
            if(Auth::user()->can('create post')){
                //$tag = null;
                //$vote = null;
                if ($request->has('tag')){
                    $tag = $request->tag;
                }
                if ($request->has('vote')){
                    $vote = $request->vote;
                }
                $post = Post::create([
                    'body' => $request->body,
                    'user_id' => Auth::user()->id,
                    'type' => $request->type,
                    'allow_comments' => $request->allow_comments,
                    'tag' => serialize($tag),
                    'vote' => serialize($vote),
                    'is_approved' => $request->is_approved,
                    'is_pinned' => $request->is_pinned,
                    'timeline_id' => $request->timeline_id  
                ]);
                Post::create(new PostResource($request->all()));
                
                if ($request->hasFile('image')) {
                // if post has media
                // upload media
                $this->createMedia($request->file('image'), $post->id, 'post');
                }
                return $this->jsonResponseWithoutMessage("Post Craeted Successfully", 'data', 200);
            }
        else{
            throw new NotAuthorized;   
        }
    }
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        $post = Post::find($request->post_id);
        if($post){
            return $this->jsonResponseWithoutMessage(new PostResource($post), 'data',200);
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
            'type' => 'required',
            'allow_comments' => 'required',
            'tag' => 'required',
            'vote' => 'required',
            'is_approved' => 'required',
            'is_pinned' => 'required',
            'timeline_id' => 'required',
            'post_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('edit post')){
            $post = Post::find($request->post_id);
            if($post){
                if(Auth::id() == $post->user_id){
                    if($request->hasFile('image')){
                        // if post has media
                        //check Media
                        $currentMedia= Media::where('post_id', $post->id)->first();
                        // if exists, update
                        if($currentMedia){
                            $this->updateMedia($request->file('image'), $currentMedia->id);
                        }
                        else {
                            // upload media
                            $this->createMedia($request->file('image'), $post->id, 'post');
                        }
                    }else{
                        $post->update($request->all());
                    }
                    return $this->jsonResponseWithoutMessage("Post Updated Successfully", 'data', 200);
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
    
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        $post = Post::find($request->post_id);
        if($post){
            if(Auth::user()->can('delete post')){
                //check Media
                $currentMedia = Media::where('post_id', $post->id)->first();
                // if exist, delete
                if ($currentMedia) {
                    $this->deleteMedia($currentMedia->id);
                }
                $post->delete();
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

    public function listAllPostsByTimelineID($timeline_id)
    {
        //find posts belong to timeline_id
        $posts = Post::where('timeline_id', $timeline_id)->get();

        if($posts){
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        }else{
            throw new NotFound();
        }
    }

    public function listAllPostsByUser($user_id)
    {
        //find posts belong to user_id
        $posts = Post::where('user_id', $user_id)->get();

        if($posts){
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        }else{
            throw new NotFound();
        }
    }
}
