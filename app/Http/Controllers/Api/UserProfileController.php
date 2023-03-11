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
use App\Http\Resources\UserExceptionResource;
use App\Models\Friend;
use App\Models\Mark;
use App\Models\Post;
use App\Models\Thesis;
use App\Models\User;
use App\Models\UserBook;
use App\Models\UserException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;

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
        $profile['info'] = new UserProfileResource(UserProfile::where('user_id',$user_id)->first());
        if ($profile['info']) {
            // user roles
            $user = User::find($user_id);
            $profile['roles'] = $user->getRoleNames();

            // reading Info
            $profile['reading_Info']['books'] = UserBook::where('user_id', $user_id)->count();
            $profile['reading_Info']['thesis'] = Thesis::where('user_id', $user_id)->count();
            // profile posts
            $profile['post']= PostResource::collection(Post::Where('timeline_id',$profile['info']['timeline_id'])->get()); 
            
            // profile friends
            $friends=$user->friends()->get();
            $friendsOf=$user->friendsOf()->get();
            $profile['friends']= $friends->merge($friendsOf);
            
            // user exceptions => displayed ONLY for Profile Owner
            if($user_id == Auth::id()){
                $profile['exceptions'] = UserExceptionResource::collection(UserException::where('user_id',$user_id)->get());
            }
            
            return $this->jsonResponseWithoutMessage($profile, 'data', 200);
        } else {
            throw new NotFound;
        }
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
            'user_profile_id' => 'required',
            //'user_id' => 'required',
            'first_name_ar' => 'required',
            'middle_name_ar' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $profile = UserProfile::find($request->user_profile_id);
        if ($profile) {
            if (Auth::id() == $profile->user_id) {

                if ($request->hasFile('image')) {
                    // if profile has media
                    //check Media
                    $currentMedia = Media::where('profile_id', $profile->id)->first();
                    // if exists, update
                    if ($currentMedia) {
                        $this->updateMedia($request->file('image'), $currentMedia->id);
                    }
                    //else create new one
                    else {
                        // upload media
                        $this->createMedia($request->file('image'), $profile->id, 'profile');
                    }
                }
                $profile->update($request->all());
                return $this->jsonResponseWithoutMessage("Profile Updated Successfully", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }

    /**
     * Get Statistics for specific user profile.
     * 
     *  @param  $user_id
     * @return jsonResponseWithoutMessage
     */
    public function profileStatistics($user_id)
    {
        $weekMark= Mark::where('week_id',1)->where('user_id',$user_id)->first();
        return $this->jsonResponseWithoutMessage($weekMark, 'data', 200);


    }
}
