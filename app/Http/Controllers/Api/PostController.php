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
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\PostResource;
use App\Models\PollOption;
use App\Models\PostType;
use App\Models\TimelineType;
use App\Models\Week;
use App\Rules\base64OrImage;
use App\Rules\base64OrImageMaxSize;
use App\Traits\PathTrait;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    use ResponseJson, MediaTraits, PathTrait;
    /**
     * Read all information about all posts of auth user in the system.
     * 
     * @return jsonResponseWithoutMessage
     */
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
    /**
     * Add a new post to the system (“create post” permission is required) 
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function create(Request $request)
    {

        $validator = $this->validatePost($request);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $user_id = Auth::id();

        if (Auth::user()->can('create post')) {

            $input['body'] = $request->body;
            $input['timeline_id'] = $request->timeline_id;
            $input['user_id'] = $user_id;

            $type_id = PostType::where('type', $request->type)->first()->id;

            $input['type_id'] = $type_id;

            $timeline = Timeline::find($request->timeline_id);

            $notification = new NotificationController();

            if (!empty($timeline)) {
                $pending_msg = null;
                $timeline_type = $timeline->type->type;
                if ($timeline_type == 'group') { //timeline type => group
                    $group = Group::where('timeline_id', $timeline->id)->first();
                    $user_types = UserGroup::where([
                        ['group_id', $group->id],
                        ['user_id', $user_id]
                    ])->pluck('user_type')->toArray();
                    $allowed_types = ['advisor', 'supervisor', 'leader', 'admin'];
                    if (!array_intersect($allowed_types, $user_types)) {
                        $input['is_approved'] = null;

                        $leader = UserGroup::where([
                            ['group_id', $group->id],
                            ['user_type', "leader"]
                        ])->first();
                        $pending_msg = "لقد نشر " . Auth::user()->name . " منشور في المجموعة " . $group->name . " يحتاج موافقة";
                        $pending_userId = $leader->user_id;
                        $pending_type = GROUP_POSTS;
                    }
                } elseif ($timeline_type == 'profile') { //timeline type => profile
                    if ($timeline->profile->user_id != Auth::id()) { // post in another profile

                        $user = User::findOrFail($timeline->profile->user_id);
                        //profileSetting => 1- for public 2- for friends 3- only me
                        if (($user->profileSetting->posts == 2 && !Friend::where('user_id', $user->id)->where('friend_id', Auth::id())->exists()) ||
                            $user->profileSetting->posts == 3
                        ) {

                            $input['is_approved'] = null;

                            $pending_msg = 'لقد قام ' . Auth::user()->name . ' بنشر منشور في صفحتك الشخصية يحتاج موافقة';
                            $pending_userId = $timeline->profile->user_id;
                            $pending_type = PROFILE_POSTS;
                        } else {
                            $pending_msg = 'لقد قام ' . Auth::user()->name . ' بنشر منشور في صفحتك الشخصية';
                            $pending_userId = $timeline->profile->user_id;
                            $pending_type = PROFILE_POSTS;
                        }
                        $pending_path = $this->getProfilePath($timeline->profile->user_id);
                    }
                } else { //timeline type => book || news || main (1-2-3)        
                    //only supervisors and above are allowed to create announcements
                    if ($request->type === 'announcement') {
                        if (!Auth::user()->can('create announcement')) {
                            throw new NotAuthorized;
                        }
                    }
                }

                if ($request->type == 'book') { //post type is book
                    $input['book_id'] = $request->book_id;
                } else {
                    $input['book_id'] = null;
                }

                $post = Post::create($input);

                if ($pending_msg) {
                    if ($pending_type === GROUP_POSTS) {
                        $pending_path = $pending_path = $this->getPendingPostsPath($group->timeline_id, $post->id);
                    }
                    $notification->sendNotification($pending_userId, $pending_msg, $pending_type, $pending_path);
                }

                if ($request->has('tags')) {
                    foreach ($request->tags as $tag) {
                        $post->taggedUsers()->create([
                            'user_id' => $tag
                        ]);
                        $notification->sendNotification($tag, Auth::user()->name . ' أشار إليك في منشور', TAGS, $this->getPostPath($post->id));
                    }
                }

                if ($request->has('votes')) {
                    foreach ($request->votes as $vote) {
                        PollOption::create([
                            'post_id' => $post->id,
                            'option' => $vote
                        ]);
                    }
                }

                if ($request->has('media') && !$request->votes) {
                    //loop through the media array and upload each media
                    foreach ($request->media as $media) {
                        $this->createMedia($media, $post->id, 'post', 'posts/' . Auth::id());
                    }
                }

                $post = $post->fresh();

                //load the post with user
                $post->load([
                    'user',
                    'pollOptions.votes' => function ($query) {
                        $query->where('user_id', Auth::id());
                    },
                    'pollVotes.votesCount',
                    'taggedUsers.user',
                ]);

                $successMsg = 'تم إضافة المنشور بنجاح';

                if ($post->is_approved === null) {
                    $successMsg = 'تم إضافة المنشور, يرجى الانتظار حتى يتم الموافقة عليه';
                }

                return $this->jsonResponse(new PostResource($post), 'data', 200, $successMsg);
            } else {
                throw new NotFound;
            }
        }
    }

    /**
     * Find an existing post in the system by its id.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function show($post_id)
    {
        $authUser = Auth::user();
        $post = Post::where('id', $post_id)
            ->whereNotNull('is_approved')
            ->withCount('comments')
            ->with('user')
            ->with('pollOptions.votes', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->withCount('pollVotes')
            ->with('taggedUsers.user')
            ->withCount('reactions')
            ->with('reactions', function ($query) use ($authUser) {
                $query->where('user_id', $authUser->id);
            })
            ->with('timeline', function ($query) {
                $query->whereIn('type_id', TimelineType::whereIn('type', ['profile', 'group'])->pluck('id'))
                    ->with('profile.user')->with('group')->with('type');
            })
            ->first();
        if ($post) {
            return $this->jsonResponseWithoutMessage(new PostResource($post), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Update an existing post in the system by the auth user.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
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

                if ($request->has('image')) {
                    // if post has media
                    //check Media
                    $currentMedia = Media::where('post_id', $post->id)->first();
                    // if exists, update
                    if ($currentMedia) {
                        $this->updateMedia($request->image, $currentMedia->id);
                    }
                    //else create new one
                    else {
                        // upload media
                        $this->createMedia($request->image, $post->id, 'post');
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
    /**
     * Delete an existing post in the system by auth user or with “delete post” permission.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function delete($post_id)
    {
        $post = Post::find($post_id);
        if ($post) {
            if (Auth::user()->can('delete post') || Auth::id() == $post->user_id) {
                DB::beginTransaction();

                try {

                    //check Media
                    $currentMedia = $post->media;
                    // if exist, delete
                    if ($currentMedia->isNotEmpty()) {
                        foreach ($currentMedia as $media) {
                            $this->deleteMedia($media->id, 'posts/' . $post->user_id);
                        }
                    }

                    //get tags
                    $tags = $post->taggedUsers;
                    //if exist, delete
                    if ($tags->isNotEmpty()) {
                        foreach ($tags as $tag) {
                            $tag->delete();
                        }
                    }

                    //delete reactions
                    $post->reactions()->delete();

                    //delete comments and their media/reactions/replies/replies reactions
                    $post->comments->each(function ($comment) {
                        $comment->reactions()->delete();

                        $media = $comment->media;
                        if ($media) {
                            $this->deleteMedia($media->id);
                        }
                    });

                    $post->delete();

                    DB::commit();

                    if (Auth::id() !== $post->id) {
                        $msg = "تم حذف منشورك  ";

                        $path = null;
                        if ($post->timeline->type->type === 'group') {
                            $path = $this->getGroupPath($post->timeline->group->id);
                            $msg .= "في المجموعة " . $post->timeline->group->name . " ";
                        } else if ($post->timeline->type->type === 'profile') {
                            $path = $this->getProfilePath($post->id);
                            $msg .= "في صفحتك الشخصية ";
                        }

                        $msg .= "من قبل " . Auth::user()->name;
                        (new NotificationController)->sendNotification($post->user_id, $msg, USER_POSTS, $path);
                    }
                    return $this->jsonResponseWithoutMessage("Post Deleted Successfully", 'data', 200);
                } catch (\Exception $e) {
                    DB::rollback();
                    return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
                }
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }
    /**
     * Return all posts that match requested timeline_id.
     * 
     * @param int  $timeline_id
     * @return jsonResponseWithoutMessage posts collection
     */
    public function postsByTimelineId($timeline_id)
    {
        $user = Auth::user();
        $posts = Post::where('timeline_id', $timeline_id)
            ->whereNotNull('is_approved')
            ->withCount('comments')
            ->with('user')
            ->with('pollOptions.votes', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->withCount('pollVotes')
            ->with('taggedUsers.user')
            ->withCount('reactions')
            ->with('reactions', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('timeline', function ($query) {
                // $query->where('type_id', TimelineType::where('type', 'profile')->first()->id)
                //     ->with('profile.user')->with('type');
                $query->whereIn('type_id', TimelineType::whereIn('type', ['profile', 'group'])->pluck('id'))
                    ->with('profile.user')->with('group.groupAdministrators')->with('type');
            })
            ->latest()
            ->paginate(25);

        $timeline_type = Timeline::find($timeline_id)->type->type;
        if ($timeline_type === 'group' || $timeline_type === 'profile') {
            $last_pinned_post = Post::where('timeline_id', $timeline_id)
                ->whereNotNull('is_approved')
                ->where('is_pinned', 1)
                ->withCount('comments')
                ->with('user')
                ->with('pollOptions.votes', function ($query) {
                    $query->where('user_id', Auth::id());
                })
                ->withCount('pollVotes')
                ->with('taggedUsers.user')
                ->withCount('reactions')
                ->with('reactions', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->with('timeline', function ($query) {
                    // $query->where('type_id', TimelineType::where('type', 'profile')->first()->id)
                    //     ->with('profile.user')->with('type');
                    $query->whereIn('type_id', TimelineType::whereIn('type', ['profile', 'group'])->pluck('id'))
                        ->with('profile.user')->with('group.groupAdministrators')->with('type');
                })
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($last_pinned_post && $posts->currentPage() == 1) {
                $posts->prepend($last_pinned_post);
            }
        }
        if ($posts->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'posts' => PostResource::collection($posts),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ], 'data', 200);
        }
        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }
    /**
     * Get posts for the auth user to display in the home page
     * Posts are selected from the timelines that the auth user is following.
     * main timeline, user timeline, groups timelines, friends timelines, friends of timelines.
     *       
     * @return jsonResponseWithoutMessage     
     */
    public function getPostsForMainPage()
    {
        $user = Auth::user()->load('userProfile', 'groups', 'friends.userProfile', 'friendsOf.userProfile');

        $posts = Post::whereIn(
            'timeline_id',
            Timeline::whereIn(
                'type_id',
                TimelineType::where('type', 'main')->pluck('id')
            )->orWhere('id', $user->userProfile->timeline_id)
                ->orWhereIn('id', $user->groups()->pluck('timeline_id'))
                ->orWhereIn('id', $user->friends()->get()->map(function ($friend) {
                    return $friend->userProfile->timeline_id;
                }))
                ->orWhereIn('id', $user->friendsOf()->get()->map(function ($friend) {
                    return $friend->userProfile->timeline_id;
                }))
                ->pluck('id')
        )
            ->where('type_id', '!=', PostType::where('type', 'announcement')->first()->id)
            ->where('type_id', '!=', PostType::where('type', 'support')->first()->id)
            ->whereNotNull('is_approved')
            ->withCount('comments')
            ->with('user')
            //check which option is selected by the user
            ->with('pollOptions.votes', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->withCount('pollVotes')
            ->with('taggedUsers.user')
            ->withCount('reactions')
            ->with('reactions', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('timeline', function ($query) {
                $query->whereIn('type_id', TimelineType::whereIn('type', ['profile', 'group'])->pluck('id'))
                    ->with('profile.user')->with('group.groupAdministrators')->with('type');
            })
            ->latest()
            ->paginate(25);

        $announcements = null;
        if ($posts->currentPage() == 1) {
            $announcements = Post::where('type_id', PostType::where('type', 'announcement')->first()->id)
                ->where('is_pinned', 1)
                ->whereNotNull('is_approved')
                ->withCount('comments')
                ->with('pollOptions.votes', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->withCount('pollVotes')
                ->with('user')
                ->with('taggedUsers.user')
                ->withCount('reactions')
                ->with('reactions', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->orderBy('updated_at', 'desc')
                ->take(1)
                ->get();

            if ($announcements->isEmpty()) {
                $announcements = Post::where('type_id', PostType::where('type', 'announcement')->first()->id)
                    ->whereNotNull('is_approved')
                    ->withCount('comments')
                    ->with('pollOptions.votes', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->withCount('pollVotes')
                    ->with('user')
                    ->with('taggedUsers.user')
                    ->withCount('reactions')
                    ->with('reactions', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->latest()
                    ->take(2)
                    ->get();
            }
        }

        if ($posts->isNotEmpty() || ($announcements && $announcements->isNotEmpty())) {
            return $this->jsonResponseWithoutMessage([
                'posts' => PostResource::collection($posts),
                'announcements' => $announcements ? PostResource::collection($announcements) : null,
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ], 'data', 200);
        }
        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }
    /**
     * Get announcements posts
     * @return jsonResponseWithoutMessage
     */
    public function getAnnouncements()
    {
        $user = Auth::user();

        $announcements = Post::where('type_id', PostType::where('type', 'announcement')->first()->id)
            ->where('timeline_id', Timeline::where('type_id', TimelineType::where('type', 'main')->first()->id)->first()->id)
            ->whereNotNull('is_approved')
            ->withCount('comments')
            ->with('pollOptions.votes', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->withCount('pollVotes')
            ->with('user')
            ->with('taggedUsers.user')
            ->withCount('reactions')
            ->with('reactions', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->paginate(25);

        $last_pinned_announcement = Post::where('type_id', PostType::where('type', 'announcement')->first()->id)
            ->where('timeline_id', Timeline::where('type_id', TimelineType::where('type', 'main')->first()->id)->first()->id)
            ->where('is_pinned', 1)
            ->whereNotNull('is_approved')
            ->withCount('comments')
            ->with('pollOptions.votes', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->withCount('pollVotes')
            ->with('user')
            ->with('taggedUsers.user')
            ->withCount('reactions')
            ->with('reactions', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($last_pinned_announcement && $announcements->currentPage() == 1) {
            $announcements->prepend($last_pinned_announcement);
        }

        if ($announcements->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'posts' => PostResource::collection($announcements),
                'total' => $announcements->total(),
                'last_page' => $announcements->lastPage(),
            ], 'data', 200);
        }
        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }
    /**
     * Get support posts
     * @return jsonResponseWithoutMessage     
     */
    public function getSupportPosts()
    {
        $user = Auth::user();
        $posts = Post::where('type_id', PostType::where('type', 'support')->first()->id)
            ->where('timeline_id', Timeline::where('type_id', TimelineType::where('type', 'main')->first()->id)->first()->id)
            ->whereNotNull('is_approved')
            ->withCount('comments')
            ->with('pollOptions.votes', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->withCount('pollVotes')
            ->with('user')
            ->with('taggedUsers.user')
            ->withCount('reactions')
            ->with('reactions', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->latest()
            ->paginate(25);

        if ($posts->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'posts' => PostResource::collection($posts),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ], 'data', 200);
        }
        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }

    /**
     * Return all posts that match requested user_id.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
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
    /**
     *Return all posts that match requested timeline_id where is_approved is null.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
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
    /**
     * Accept post that matches the required post_id where is_approved = null,
     * give date for this approval and send notification to user
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function acceptPost($post_id)
    {
        $post = Post::find($post_id);
        if (!$post) {
            throw new NotFound;
        }

        if ($post->is_approved == Null) {
            $post->is_approved = now();
            $post->update();

            $msg = "لقد تم قبول منشورك من قبل " . Auth::user()->name;
            (new NotificationController)->sendNotification($post->user_id, $msg, USER_POSTS, $this->getPostPath($post->id));
            return $this->jsonResponseWithoutMessage("The post is approved successfully", 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage("The post is already approved ", 'data', 200);
        }
    }
    /**
     * Decline post that matches the required post_id where is_approved = null,
     * delete post and send notification to user
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function declinePost($post_id)
    {
        $post = Post::find($post_id);

        if (!$post) {
            throw new NotFound;
        }

        if ($post->is_approved == Null) {
            $this->delete($post_id);
            $msg = "تم رفض منشورك وحذفه من قبل " . Auth::user()->name;

            $path = "";
            if ($post->timeline->type->type === 'group') {
                $path = $this->getGroupPath($post->timeline->group->id);
            } else {
                $path = $this->getProfilePath(Auth::id());
            }
            (new NotificationController)->sendNotification($post->user_id, $msg, USER_POSTS, $path);
            return $this->jsonResponseWithoutMessage("The post is deleted successfully", 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage("The post is already approved ", 'data', 200);
        }
    }
    /**
     * user can control comments in the system (“control comments” permission is required)
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function controlComments($post_id)
    {

        $post = Post::find($post_id);

        if ($post) {
            if (Auth::id() == $post->user_id || (Auth::user()->can('control comments') && $post->timeline->type->type === 'group')) {
                $post->allow_comments = $post->allow_comments ? 0 : 1;
                $post->save();

                if ($post->allow_comments == 0) {
                    $msg = "closed";
                } else {
                    $msg = "openned";
                }

                return $this->jsonResponseWithoutMessage($msg, 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }
    /**
     * User can pin post on his profile or if user has a pin post permission.
     * posts can be pinned only on the timelines ['announcement', 'group', 'profile']
     * steps:
     * 1- check if post exist
     * 2- check if user has a pin post permission or if user is allowed to pin post on his timeline
     * 3- change is_pinned value to 1 or 0
     * 4- unpin other posts if the post is pinned
     * 5- return message
     * 
     * @param  int  $post_id
     * @return jsonResponseWithoutMessage
     */
    public function pinPost($post_id)
    {
        $post = Post::find($post_id);
        if ($post) {
            if ((Auth::id() === $post->user_id && Auth::user()->userProfile->timeline_id === $post->timeline_id) ||
                (Auth::user()->can('pin post') && $post->timeline->type->type === 'group') ||
                (Auth::user()->can('pin post') && $post->type->type === 'announcement')
            ) {

                $post->is_pinned = $post->is_pinned ? 0 : 1;
                $post->save();

                //unpin other posts
                if ($post->is_pinned === 1) {
                    Post::where([
                        ['timeline_id', $post->timeline_id],
                        ['is_pinned', 1],
                        ['id', '!=', $post->id]
                    ])->update(['is_pinned' => 0]);
                }

                if ($post->is_pinned === 0) {
                    $msg = "unpinned";
                } else {
                    $msg = "pinned";
                }
                return $this->jsonResponseWithoutMessage($msg, 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }

    /**
     * Validate post request
     * 
     * @param  Request  $request
     * @return Validator
     */
    public function validatePost(Request $request)
    {
        $post_types = PostType::all()->pluck('type')->toArray();

        return Validator::make($request->all(), [
            'body' => 'required_with:votes|required_without:media|nullable|string',
            'type' => 'required|string|in:' . implode(',', $post_types),
            'timeline_id' => 'required|integer',
            'tags' => 'nullable|array',
            'tags.*' => 'integer',
            'votes' => 'nullable|array',
            'votes.*' => 'string',
            'media' => 'required_without_all:body,votes|array',
            'media.*' => [
                function ($attribute, $value, $fail) {
                    //check if is it image (base64 or image)

                    $is_image = Validator::make(
                        ['upload' => $value],
                        ['upload' => new base64OrImage()]
                    )->passes();

                    //check if is it video
                    $is_video = Validator::make(
                        ['upload' => $value],
                        ['upload' => 'mimetypes:video/avi,video/mpeg,video/quicktime,video/mp4']
                    )->passes();

                    //return error if not image or video
                    if (!$is_video && !$is_image) {
                        $fail(':attribute must be image (.png, .jpeg, .jpg) or video.');
                    }

                    //if video, check if it is less than 10MB
                    if ($is_video) {
                        $validator = Validator::make(
                            ['video' => $value],
                            ['video' => "max:102400"]
                        );
                        if ($validator->fails()) {
                            $fail(":attribute must be 10 megabytes or less.");
                        }
                    }

                    //if image, check if it is less than 2MB
                    if ($is_image) {
                        $validator = Validator::make(
                            ['image' => $value],
                            ['image' => new base64OrImageMaxSize(2 * 1024 * 1024)]
                        );
                        if ($validator->fails()) {
                            $fail(":attribute must be two megabytes or less.");
                        }
                    }
                },
            ],
        ]);
    }

    public function getLastSupportPost()
    {
        $currentWeek = Week::latest()->first();
        $createdAt = $currentWeek->created_at;
        $mainTimer = $currentWeek->main_timer;
        $post = Post::where('type_id', PostType::where('type', 'support')->first()->id)
            ->where('created_at', '>=', $createdAt)
            ->where('created_at', '<', $mainTimer)
            ->latest()->first();

        if ($post) {
            return $this->jsonResponseWithoutMessage($post, 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage(null, 'data', 200);
        }
    }

    /**
     * Get pending posts by timeline id and post id if exists
     * @param Int $timeline_id
     * @param Int $post_id
     * @return JsonResponse
     */
    public function getPendingPosts($timeline_id, $post_id = null)
    {
        //check if timeline exists        
        $timeline = Timeline::find($timeline_id);
        if (!$timeline) {
            throw new NotFound;
        }

        //check if timeline is profile or group
        $timeline_type = $timeline->type->type;
        if ($timeline_type != 'profile' && $timeline_type != 'group') {
            throw new NotFound;
        }

        //check if user can view pending posts
        //if profile check if user is the owner of the profile
        //if group check if user is a leader or above
        if ($timeline_type == 'profile') {
            if (Auth::id() != $timeline->profile->user_id) {
                throw new NotAuthorized;
            }
        } else {
            if (!Auth::user()->hasAnyRole(['leader', 'admin', 'supervisor', 'advisor', 'consultant'])) {
                throw new NotAuthorized;
            }
        }

        //check if post exists
        if ($post_id) {
            $post = Post::where([
                ['timeline_id', $timeline_id],
                ['id', $post_id]
            ])
                ->withCount('comments')
                ->with('user')
                ->withCount('pollVotes')
                ->with('taggedUsers.user')

                ->with('timeline', function ($query) {
                    $query->with('profile.user')->with('group')->with('type');
                })
                ->first();
            if (!$post) {
                throw new NotFound;
            } else {
                return $this->jsonResponseWithoutMessage([
                    'posts' => [new PostResource($post)],
                ], 'data', 200);
            }
        }

        //get pending posts
        $posts = Post::where([
            ['timeline_id', $timeline_id],
            ['is_approved', null]
        ])
            ->withCount('comments')
            ->with('user')
            ->withCount('pollVotes')
            ->with('taggedUsers.user')

            ->with('timeline', function ($query) {
                $query->with('profile.user')->with('group')->with('type');
            })
            ->latest()
            ->get();

        // ->paginate(25);

        if ($posts->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'posts' => PostResource::collection($posts),
                // 'total' => $posts->total(),
                // 'last_page' => $posts->lastPage(),
            ], 'data', 200);
        }
        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }
}