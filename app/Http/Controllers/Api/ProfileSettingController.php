<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProfileSetting;
use App\Models\Media;
use App\Http\Resources\ProfileSettingResource;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;

class ProfileSettingController extends Controller
{
    use ResponseJson, MediaTraits;
    /**
     * Find existing profile setting in the system by auth user.
     *
     * @return jsonResponseWithoutMessage;
     */
    public function show()
    {
        //Profile Settings belong to Auth user
        $settings = ProfileSetting::where('user_id', Auth::id())->get();

        if($settings->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(ProfileSettingResource::collection($settings), 'data', 200);
        }else{
            throw new NotFound;
        }
    }
    /**
     * Update existing profile setting in the system by the auth user.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_setting_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        
        $setting = ProfileSetting::find($request->profile_setting_id);
        if($setting){
            if(Auth::id() == $setting->user_id){
                $setting->update($request->all());
                return $this->jsonResponseWithoutMessage("Profile Settings Updated Successfully", 'data', 200);
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
