<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\UserProfile;
use App\Models\Media;
use App\Http\Resources\UserProfileResource;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    use ResponseJson, MediaTraits;
    
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        $profile = UserProfile::find($request->user_id);
        if($profile){
            return $this->jsonResponseWithoutMessage(new UserProfileResource($profile), 'data',200);
        }
        else{
           throw new NotFound;
        }
    }

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
        if($profile){
            if(Auth::id() == $profile->user_id){
               
                if($request->hasFile('image')){
                    // if profile has media
                    //check Media
                    $currentMedia= Media::where('profile_id', $profile->id)->first();
                    // if exists, update
                    if($currentMedia){
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
