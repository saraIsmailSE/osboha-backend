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

use function PHPUnit\Framework\isNull;

class PostController extends Controller
{
    use ResponseJson, MediaTraits;
   
    public function index()
    {
        $posts = Post::where('user_id', Auth::id())->get();

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
            'body' => 'required_without_all:image',
            'type_id' => 'required',
            'timeline_id' => 'required',
<<<<<<< HEAD
            //'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048 required_without:body',
=======
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048|required_without_all:body',
            'tag' => 'array',
            'vote' => 'array',
>>>>>>> f8263cc8d84c69b7cd7445f682b3fe4492efe3ed
        ]);
    
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (Auth::user()->can('create post')) {

            $postData = $request->all();
            $postData['user_id'] = Auth::id();

<<<<<<< HEAD
            if(!empty($timeline) ){ 
                if($timeline->type_id=="group") {
                $group = Group::where('timeline_id',$timeline->id)->first();
                $user = UserGroup::where([
                    ['group_id', $group->id],
                    ['user_id', Auth::id()]
                ])->first();
                if($user->user_type != "advisor" || $user->user_type != "supervisor" || $user->user_type != "leader"){
                    $input['is_approved'] = null;
                    echo 'waiting for the leader approval'; 

                    $msg = "hi";
                    (new NotificationController)->sendNotification(Auth::id() , $msg);

                    $leader =UserGroup::where([
                        ['group_id', $group->id],
                        ['user_type', "leader"]
                    ])->first(); 
                    $msg = "There are new posts need approval";
                    (new NotificationController)->sendNotification($leader->user_id , $msg);
                }
=======
            // tag: array of user id 
            if ($request->has('tag')) {
                $postData['tag'] = serialize($request->tag);
            }
            // vote: array of options 
            if ($request->has('vote')) {
                $postData['vote'] = serialize($request->vote);
>>>>>>> f8263cc8d84c69b7cd7445f682b3fe4492efe3ed
            }

            $timeline = Timeline::find($request->timeline_id);

            if ($timeline) {
                if ($timeline->type->type == "group") {
                    // get group information
                    $group = Group::where('timeline_id', $timeline->id)->first();

                    // get user role in the group
                    $userInGroup = UserGroup::where([
                        ['group_id', $group->id],
                        ['user_id', Auth::id()]
                    ])->first();
                    //if user role in a group is an ambassador then post need leader\supervisor\advisor approvement 
                    if ($userInGroup->user_type == "ambassador") {
                        $postData['is_approved'] = null;
                    }
                }

                $post = Post::create($postData);
                // if the post need approval inform each ambassador and leader 
                if(is_null($postData['is_approved'])){
                    //inform user 
                    $msg = "your post needs leader approval";
                    (new NotificationController)->sendNotification(Auth::id(), $msg);

                    // inform leader
                    $leader = UserGroup::where([
                        ['group_id', $group->id],
                        ['user_type', "leader"]
                    ])->first();
                    $msg = "There are new posts need approval";
                    (new NotificationController)->sendNotification($leader->user_id, $msg);
                }
                    
                if ($request->hasFile('image')) {
                    // if post has image
                    // upload image
                    $this->createMedia($request->file('image'), $post->id, 'post');
                }
                return $this->jsonResponseWithoutMessage("Post Craeted Successfully", 'data', 200);
            } 
            else {
                throw new NotFound;
            }
        } 
        else {
            throw new NotAuthorized;
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
            'type_id' => 'required',
            'timeline_id' => 'required',
            'post_id' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048 required_without:body',
            'tag' => 'array',
            'vote' => 'array'
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
        $validator = Validator::make($request->all(), [
            'timeline_id' => 'required',
        ]);
        
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        //find posts belong to timeline_id
        $posts = Post::where('timeline_id', $request->timeline_id)->get();

        if($posts->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        }else{
            throw new NotFound();
        }
    }

    public function postByUserId(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);
        
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //find posts belong to user_id
        $posts = Post::where('user_id', $request->user_id)->get();

        if($posts->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        }else{
            throw new NotFound();
        }
    }

    public function listPostsToAccept (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'timeline_id' => 'required',     
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (Auth::user()->can('accept post')) {
            $posts = Post::where([
                ['timeline_id', $request->timeline_id],
                ['is_approved', Null]
            ])->get();
            if ($posts->isNotEmpty()) {
                return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
            } else {
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;
        }

        
    }

    public function AcceptPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (Auth::user()->can('accept post')) {
            $post = Post::find($request->post_id);
            if ($post) {
                if ($post->is_approved == Null) {
                    $post->is_approved = now();
                    $post->update();

                    $msg = "Your post is approved successfully";
                    (new NotificationController)->sendNotification($post->user_id, $msg);
                    return $this->jsonResponseWithoutMessage("The post is approved successfully", 'data', 200);
                } else {
                    return $this->jsonResponseWithoutMessage("The post is already approved ", 'data', 200);
                }
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    public function declinePost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',     
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (Auth::user()->can('decline post')) {
            $post = Post::find($request->post_id);
            if ($post) {
                if ($post->is_approved == Null) {
                    $post->delete();
                    $msg = "Your post is declined";
                    (new NotificationController)->sendNotification($post->user_id, $msg);
                    return $this->jsonResponseWithoutMessage("The post is deleted successfully", 'data', 200);
                } else {
                    return $this->jsonResponseWithoutMessage("The post is already approved ", 'data', 200);
                }
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
    
    public function controllComments(Request $request){
        // user can controll comments [allowed or not]  if he is the owner or has a controll comments permission
        $validator = Validator::make($request->all(), [
            'allow_comments' => 'required',
            'post_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        $post = Post::find($request->post_id);
        if($post){
            if(Auth::id() == $post->user_id || Auth::user()->can('controll comments')){
                $post->allow_comments=$request->allow_comments;
                $post->save();

                if($request->allow_comments == 0 ){
                    $msg = "Comments Closed Successfully";
                }
                else{
                    $msg = "Comments Opend Successfully";
                }
                return $this->jsonResponseWithoutMessage($msg, 'data', 200);

            }    
            else{
                throw new NotAuthorized;   
            }
        }
        else{
            throw new NotFound;   
        } 

    }

    public function pinnPost(Request $request){
        // user can pin post on his profile or if he has a pin post permission
        $validator = Validator::make($request->all(), [
            'is_pinned' => 'required',
            'post_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        $post = Post::find($request->post_id);
        if($post){
            if(Auth::user()->userProfile->timeline_id == $post->timeline_id || Auth::user()->can('pin ')){
               
                Post::where('id',$request->post_id)->update(['is_pinned'=>$request->is_pinned]);
                if($request->is_pinned == 0 ){
                    $msg = "Post Unpinned Successfully";
                }
                else{
                    $msg = "Post Pinned Successfully";
                }
                return $this->jsonResponseWithoutMessage($msg, 'data', 200);
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
