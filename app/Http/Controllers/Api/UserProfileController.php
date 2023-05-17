<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\Media;
use App\Http\Resources\UserProfileResource;
use App\Http\Resources\PostResource;
use App\Http\Resources\FriendResource;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\BookResource;
use App\Http\Resources\UserExceptionResource;
use App\Http\Resources\UserInfoResource;
use App\Models\Friend;
use App\Models\Group;
use App\Models\Mark;
use App\Models\Post;
use App\Models\Section;
use App\Models\SocialMedia;
use App\Models\Thesis;
use App\Models\User;
use App\Models\UserBook;
use App\Models\UserException;
use App\Models\UserGroup;
use App\Models\Week;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

class UserProfileController extends Controller
{
    use ResponseJson, MediaTraits;

    /**
     * Find an existing profile by user id in the system and display it.
     *
     * @param user_id
     * @return jsonResponse[profile:info,posts,friends,exceptions]
     */
    public function show($user_id)
    {
        $profile['info'] = UserProfile::where('user_id', $user_id)->first();
        if ($profile['info']) {

            // social media
            $profile['social_media'] = SocialMedia::where('user_id', $user_id)->first();

            $user = User::find($user_id);

            // user
            $profile['user'] = $user;
            // user roles
            $profile['roles'] = $user->getRoleNames();

            // reading Info
            $profile['reading_Info']['books'] = UserBook::where('user_id', $user_id)->count();
            $profile['reading_Info']['thesis'] = Thesis::where('user_id', $user_id)->count();

            //books
            $userBooks =  UserBook::where(function ($query) {
                $query->Where('status', 'in progress')->orWhere('status', 'finished');
            })->where('user_id', $user_id)->get()->pluck('book');
            $profile['books'] = BookResource::collection($userBooks);

            // profile posts
            $profile['post'] = PostResource::collection(Post::Where('timeline_id', $profile['info']['timeline_id'])->get());

            // profile friends
            $friends = $user->friends()->get();
            $friendsOf = $user->friendsOf()->get();
            $allfriends = $friends->merge($friendsOf);
            //get 10 friends (just id and name)
            $profile['friends'] = $allfriends->take(9)->map(function ($friend) {
                return new UserInfoResource($friend);
            });

            if ($user_id == Auth::id()) {
                // user exceptions => displayed ONLY for Profile Owner
                $profile['exceptions'] = UserException::where('user_id', $user_id)->get();
            } else {
                // friend with auth or not
                $profile['friendWithAuth'] = Friend::where(function ($q) use ($user_id) {
                    $q->where('user_id', Auth::id())
                        ->Where('friend_id', $user_id)
                        ->where('status', 1);
                })->orWhere(function ($q) use ($user_id) {
                    $q->where('user_id', $user_id)
                        ->Where('friend_id', Auth::id())
                        ->where('status', 1);
                })
                    ->exists();

                $profile['friendRequestByAuth'] = Friend::where(function ($q) use ($user_id) {
                    $q->where('user_id', Auth::id())
                        ->Where('friend_id', $user_id)
                        ->where('status', 0);
                })->exists();

                $profile['friendRequestByFriend'] = Friend::where(function ($q) use ($user_id) {
                    $q->where('user_id', $user_id)
                        ->Where('friend_id', Auth::id())
                        ->where('status', 0);
                })
                    ->exists();

                $friendship = Friend::where(function ($q) use ($user_id) {
                    $q->where('user_id', Auth::id())
                        ->Where('friend_id', $user_id);
                })->orWhere(function ($q) use ($user_id) {
                    $q->where('user_id', $user_id)
                        ->Where('friend_id', Auth::id());
                })
                    ->first();
                $profile['friendship_id'] =  $friendship ?  $friendship->id : null;
            }

            return $this->jsonResponseWithoutMessage($profile, 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    /**
     * Find an existing profile by user id in the system and display it.
     *
     * @param user_id
     * @return jsonResponse[profile:info]
     */
    public function showToUpdate()
    {
        $response['profileInfo'] = UserProfile::where('user_id', Auth::id())->first();
        $response['sections'] = Section::all();
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    /**
     * Update an existing profile by the auth user in the system .
     * 
     *  @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name_ar' => 'required',
            'last_name_ar' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $profile = UserProfile::where('user_id', Auth::id());
        if ($profile) {
            $profile->update(
                $request->only(
                    'first_name_ar',
                    'middle_name_ar',
                    'last_name_ar',
                    'country',
                    'resident',
                    'birthdate',
                    'bio',
                    'fav_book',
                    'fav_writer',
                    'fav_quote',
                    'fav_section'
                )
            );
            return $this->jsonResponseWithoutMessage("تم التحديث بنجاح", 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    /**
     * update profile picture.
     * 
     *  @param  $request contains profile_picture
     * @return jsonResponseWithoutMessage
     */
    public function updateProfilePic(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "profile_picture" => "required|image|mimes:png,jpg,jpeg,gif,svg",
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }
        try {
            $profile = UserProfile::where('user_id', Auth::id())->first();
            $folderName = "profile_" . $profile->id;
            $imageName = $this->createProfileMedia($request->file('profile_picture'), $folderName);
            $profile->profile_picture = $imageName;
            $profile->save();

            //resize
            //60x60
            $imagePath = 'assets/images/profiles/' . $folderName . '/' . $imageName;
            $pathToSave = 'assets/images/profiles/' . $folderName;
            $this->resizeImage(60, 60, $imagePath, $pathToSave, $imageName);
            //100x100
            $imagePath = 'assets/images/profiles/' . $folderName . '/' . $imageName;
            $pathToSave = 'assets/images/profiles/' . $folderName;
            $this->resizeImage(100, 100, $imagePath, $pathToSave, $imageName);
            //150x150
            $imagePath = 'assets/images/profiles/' . $folderName . '/' . $imageName;
            $pathToSave = 'assets/images/profiles/' . $folderName;
            $this->resizeImage(150, 150, $imagePath, $pathToSave, $imageName);
            //512x512
            $imagePath = 'assets/images/profiles/' . $folderName . '/' . $imageName;
            $pathToSave = 'assets/images/profiles/' . $folderName;
            $this->resizeImage(512, 512, $imagePath, $pathToSave, $imageName);

            return $this->jsonResponseWithoutMessage($profile, 'data', 200);
        } catch (\Exception $e) {
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    /**
     * update profile picture.
     * 
     *  @param  $request contains cover_picture
     * @return jsonResponseWithoutMessage
     */
    public function updateProfileCover(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "cover_picture" => "required|image|mimes:png,jpg,jpeg,gif,svg",
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors());
        }

        try {
            $profile = UserProfile::where('user_id', Auth::id())->first();
            $folderName = "profile_" . $profile->id;
            $imageName = $this->createProfileMedia($request->file('cover_picture'), $folderName);
            $profile->cover_picture = $imageName;
            $profile->save();

            // //resize
            // //1300x325
            $imagePath = 'assets/images/profiles/' . $folderName . '/' . $imageName;
            $pathToSave = 'assets/images/profiles/' . $folderName;
            $this->resizeImage(1300, 325, $imagePath, $pathToSave, $imageName);

            return $this->jsonResponseWithoutMessage($profile, 'data', 200);
        } catch (\Exception $e) {
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    public function getImages($profile_id, $file_name)
    {
        $folderName = "profile_" . $profile_id;
        $path = public_path() . '/assets/images/profiles/' . $folderName . '/' . $file_name;

        if (!File::exists($path)) {
            throw new NotFound;
        }

        return response()->download($path, $file_name);
    }



    /**
     * Get Statistics for specific user profile.
     * 
     *  @param  $user_id
     * @return jsonResponseWithoutMessage
     */
    public function profileStatistics($user_id)
    {
        $response['week'] = Week::latest()->first();
        $group_id = UserGroup::where('user_id', Auth::id())->where('user_type', 'ambassador')->pluck('group_id')->first();
        $users = Group::with('leaderAndAmbassadors')->where('id', $group_id);
        $response['group_week_avg'] = Mark::where('week_id', $response['week']->id)->whereIn('user_id', $users->pluck('id'))
            ->select(DB::raw('avg(reading_mark + writing_mark + support) as out_of_100'))
            ->first()
            ->out_of_100;

        $response['week_mark'] = Mark::where('week_id', $response['week']->id)->where('user_id', $user_id)->first();

        // $currentMonth = date('m');        
        $currentMonth = date('m', strtotime($response['week']->created_at));
        $weeksInMonth = Week::whereRaw('MONTH(created_at) = ?', $currentMonth)->get();

        $response['month_achievement'] = Mark::where('user_id', $user_id)
            ->whereIn('week_id', $weeksInMonth->pluck('id'))
            ->select(DB::raw('avg(reading_mark + writing_mark + support) as out_of_100 , week_id'))
            ->groupBy('week_id')
            ->get()
            ->pluck('out_of_100', 'week.title');

        $response['month_achievement_title'] = Week::whereIn('id', $weeksInMonth->pluck('id'))->pluck('title')->first();

        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }
}