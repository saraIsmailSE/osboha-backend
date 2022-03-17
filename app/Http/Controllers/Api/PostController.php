<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Media;
use App\Models\Timeline;
use App\Models\UserGroup;
use App\Models\Group;
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
    use ResponseJson, MediaTraits;
   
    public function index()
    {
        //$posts = Post::all();
        $posts = Post::where('user_id', Auth::id())->get();
        //$posts = Post::where('timeline_id', $timeline_id)->get();

        if($posts->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data',200);
        }
        else{
            throw new NotFound;
        }
    }
    
    public function create(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'body' => 'required_without:image',
            'user_id' => 'required',
            'type' => 'required',
            'timeline_id' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048 required_without:body',
        ]);
     
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
     
        if(Auth::user()->can('create post')){
            $input = $request->all();
            $timeline = Timeline::find($request->timeline_id);

            if($timeline){ 
                if($timeline->type=="group") {
                $group = Group::where('timeline_id',$timeline->id)->get();
                
                $user = UserGroup::where([
                    ['group_id', $group->id],
                    ['user_id', Auth::id()]
                ])->get();

                if($user->user_type != "advisor" || $user->user_type != "supervisor" || $user->user_type != "leader"){
                    $input['is_approved'] = null;
                }
                }
                
                if ($request->has('tag')){
                    $input['tag'] = serialize($request->tag);
                }

                if ($request->has('vote')){
                    $input['vote'] = serialize($request->vote);
                }

                $input['user_id'] = Auth::id();

                $post= Post::create($input);

                if ($request->hasFile('image')) {
                // if post has media
                // upload media
                $this->createMedia($request->file('image'), $post->id, 'post');
                }
                return $this->jsonResponseWithoutMessage("Post Craeted Successfully", 'data', 200);
            }
            else{
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized; 
            echo 'waiting for the leader approval'; 
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
            'body' => 'required_without:image',
            'user_id' => 'required',
            'type' => 'required',
            //'allow_comments' => 'required',
            //'tag' => 'required',
            //'vote' => 'required',
            //'is_approved' => 'required',
            //'is_pinned' => 'required',
            'timeline_id' => 'required',
            //'post_id' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048 required_without:body'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        $post = Post::find($request->post_id);
        if($post){
            if(Auth::id() == $post->user_id){
                $input = $request->all();
                if ($request->has('tag')){
                $input['tag'] = serialize($request->tag);
                }

                if ($request->has('vote')){
                    $input['vote'] = serialize($request->vote);
                }

                if($request->hasFile('image')){
                    // if post has media
                    //check Media
                    $currentMedia= Media::where('post_id', $post->id)->first();
                    // if exists, update
                    if($currentMedia){
                        $this->updateMedia($request->file('image'), $currentMedia->id);
                    }
                    //else create new one
                    else {
                        // upload media
                        $this->createMedia($request->file('image'), $post->id, 'post');
                    }
                } 
                $post->update($input);
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
            if(Auth::user()->can('delete post') || Auth::id() == $post->user_id){
                //check Media
                $currentMedia = Media::where('post_id', $post->id)->first();
                // if exist, delete
                if ($currentMedia) {
                    $this->deleteMedia($currentMedia->id);
                }
                $post->delete();
                return $this->jsonResponseWithoutMessage("Post Deleted Successfully", 'data', 200);
            }
            else{
                throw new NotAuthorized;
            }
        }
        else{
            throw new NotFound;   
        }
    }

    public function postByTimelineId(Request $request)
    {
        $timeline_id = $request->timeline_id;

        //find posts belong to timeline_id
        $posts = Post::where('timeline_id', $timeline_id)->get();

        if($posts->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        }else{
            throw new NotFound();
        }
    }

    public function postByUserId(Request $request)
    {
        $user_id = $request->user_id;
        //find posts belong to user_id
        $posts = Post::where('user_id', $user_id)->get();

        if($posts->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        }else{
            throw new NotFound();
        }
    }
}
