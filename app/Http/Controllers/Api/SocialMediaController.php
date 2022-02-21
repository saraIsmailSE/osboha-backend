<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use App\Http\Resources\socialMediaResource ;
use App\Models\SocialMedia;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;

class SocialMediaController extends Controller
{
    use ResponseJson;

    public function create(Request $request)
    {
        $userExists = SocialMedia::where('user_id',Auth::id())->count();
        if($userExists == 0){
            $validator = Validator::make($request->all(), [
                'facebook' => 'required_without_all:twitter,instagram',
                'twitter' => 'required_without_all:facebook,instagram',
                'instagram' => 'required_without_all:facebook,twitter',
            ]);
            if ($validator->fails()) {
                return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
            } 

            $request['user_id'] = Auth::id();
            SocialMedia::create($request->all());
            return $this->jsonResponseWithoutMessage("Your Accounts Are Added Successfully", 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage("You can't Add Anothe Social Media Accounts", 'data', 500);
        }
    }

    
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if($request->user_id == Auth::id()){
            $socialMedia = SocialMedia::where('user_id',Auth::id())->first();
            if($socialMedia){
                $socialMedia = new socialMediaResource($socialMedia);
                return $this->jsonResponseWithoutMessage($socialMedia, 'data',200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'facebook' => 'required_without_all:twitter,instagram',
            'twitter' => 'required_without_all:facebook,instagram',
            'instagram' => 'required_without_all:facebook,twitter',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
    
        if($request->user_id == Auth::id()){
            $socialMedia = SocialMedia::where('user_id',Auth::id())->first();
            $socialMedia->update($request->all());
            return $this->jsonResponseWithoutMessage("Your Accounts Are Updated Successfully", 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if($request->user_id == Auth::id()){
            $socialMedia = SocialMedia::where('user_id',Auth::id())->first();
            $socialMedia->delete();
            return $this->jsonResponseWithoutMessage("Your Accounts Are Deleted Successfully", 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }
}
