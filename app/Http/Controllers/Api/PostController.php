<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Media;
use App\Models\Friend;
use App\Models\Timeline;
use App\Models\UserGroup;
use App\Models\Group;
use App\Models\User;
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

        if ($posts->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    public function create(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'body' => 'required_without:image',
            'type' => 'required',
            'timeline_id' => 'required',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048 required_without:body',
            'book_id' => 'required_if:type,==,1' //post type = 1 => book
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
         
            $input = $request->all();
            $timeline = Timeline::find($request->timeline_id);

            if (!empty($timeline)) {
                if ($timeline->type_id == 4) { //timeline type => group
                    $group = Group::where('timeline_id', $timeline->id)->first();
                    $user = UserGroup::where([
                        ['group_id', $group->id],
                        ['user_id', Auth::id()]
                    ])->first();
                    if ($user->user_type != "advisor" || $user->user_type != "supervisor" || $user->user_type != "leader") {
                        $input['is_approved'] = null;

                        echo 'waiting for the leader approval';

                        $leader = UserGroup::where([
                            ['group_id', $group->id],
                            ['user_type', "leader"]
                        ])->first();
                        $msg = "There are new posts need approval";
                        (new NotificationController)->sendNotification($leader->user_id, $msg);
                    }

                } elseif ($timeline->type_id == 5) { //timeline type => profile
                    if($timeline->profile->user_id != Auth::id()) { // post in another profile

                        $user = User::findOrFail($timeline->profile->user_id);
                        //profileSetting => 1- for public 2- for friends 3- only me
                        if ( ($user->profileSetting->posts == 2 && !Friend::where('user_id',$user->id)->where('friend_id',Auth::id())->exists()) ||
                            $user->profileSetting->posts == 3 ) 
                        {

                            $input['is_approved'] = null;
                            $msg = "You have a new post in your profile need approval ";
                            (new NotificationController)->sendNotification($timeline->profile->user_id, $msg);

                        } 
                    }

                } else { //timeline type => book || news || main (1-2-3)
                    if (!Auth::user()->can('create post')) {
                        throw new NotAuthorized;
                    }
                } 

                if ($request->has('vote')) {
                    $input['vote'] = serialize($request->vote);
                }

                if ($request->type == 1) { //post type is book
                    $input['book_id'] = $request->book_id;
                } else {
                    $input['book_id'] = null;
                }

                $input['user_id'] = Auth::id();
                $input['type_id'] = $request->type;

                $post = Post::create($input);

                if ($request->hasFile('image')) {
                    // if post has media
                    // upload media
                    $this->createMedia($request->file('image'), $post->id, 'post');
                }
                return $this->jsonResponseWithoutMessage("Post Craeted Successfully", 'data', 200);
            } else {
                throw new NotFound;
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
        if ($post) {
            return $this->jsonResponseWithoutMessage(new PostResource($post), 'data', 200);
        } else {
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
        if ($post) {
            if (Auth::id() == $post->user_id) {
                $input = $request->all();
                if ($request->has('tag')) {
                    $input['tag'] = serialize($request->tag);
                }

                if ($request->has('vote')) {
                    $input['vote'] = serialize($request->vote);
                }

                if ($request->hasFile('image')) {
                    // if post has media
                    //check Media
                    $currentMedia = Media::where('post_id', $post->id)->first();
                    // if exists, update
                    if ($currentMedia) {
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
            } else {
                throw new NotAuthorized;
            }
        } else {
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
        if ($post) {
            if (Auth::user()->can('delete post') || Auth::id() == $post->user_id) {
                //check Media
                $currentMedia = Media::where('post_id', $post->id)->first();
                // if exist, delete
                if ($currentMedia) {
                    $this->deleteMedia($currentMedia->id);
                }
                $post->delete();
                return $this->jsonResponseWithoutMessage("Post Deleted Successfully", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }

    public function postByTimelineId(Request $request)
    {
        $timeline_id = $request->timeline_id;

        //find posts belong to timeline_id
        $posts = Post::where('timeline_id', $timeline_id)->get();

        if ($posts->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        } else {
            throw new NotFound();
        }
    }

    public function postByUserId(Request $request)
    {
        $user_id = $request->user_id;
        //find posts belong to user_id
        $posts = Post::where('user_id', $user_id)->get();

        if ($posts->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(PostResource::collection($posts), 'data', 200);
        } else {
            throw new NotFound();
        }
    }

    public function listPostsToAccept(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'timeline_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

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

    public function AcceptPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $post = Post::find($request->post_id);
        if ($post->is_approved == Null) {
            $post->is_approved = now();
            $post->update();

            $msg = "Your post is approved successfully";
            (new NotificationController)->sendNotification($post->user_id, $msg);
            return $this->jsonResponseWithoutMessage("The post is approved successfully", 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage("The post is already approved ", 'data', 200);
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

        $post = Post::find($request->post_id);
        if ($post->is_approved == Null) {
            $post->delete();
            $msg = "Your post is declined";
            (new NotificationController)->sendNotification($post->user_id, $msg);
            return $this->jsonResponseWithoutMessage("The post is deleted successfully", 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage("The post is already approved ", 'data', 200);
        }
    }
}
