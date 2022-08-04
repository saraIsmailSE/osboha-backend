<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use App\Http\Resources\socialMediaResource ;
use App\Models\SocialMedia;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;


class SocialMediaController extends Controller
{
    use ResponseJson;

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'facebook' => 'required_without_all:twitter,instagram',
            'twitter' => 'required_without_all:facebook,instagram',
            'instagram' => 'required_without_all:facebook,twitter',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $userExists = SocialMedia::where('user_id',Auth::id())->first();
        if($userExists){
            return $this->jsonResponseWithoutMessage("Your Account is already Exist, You Can Update It", 'data', 500);
        } else { 
            $request['user_id'] = Auth::id();
            SocialMedia::create($request->all());
            return $this->jsonResponseWithoutMessage("Your Accounts Are Added Successfully", 'data', 200);
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
                return $this->jsonResponseWithoutMessage(new socialMediaResource($socialMedia), 'data',200);
            } 
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

        $userExists = SocialMedia::where('user_id',Auth::id())->first();
        if($userExists){
            return $this->jsonResponseWithoutMessage("Your Social Media is already Exist, You Can Update It", 'data', 500);
        } else { 
            $request['user_id'] = Auth::id();
            SocialMedia::update($request->all());
            return $this->jsonResponseWithoutMessage("Your Accounts Are Updated Successfully", 'data', 200);
        }
    }
    
    /* delete(user_id)
        ** function to dalete all social media accounts for a user
        ** Take one parameter => user_id
    */
    public function delete(Request $request)
    {
        {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
            ]);
            if ($validator->fails()) {
                return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
            }
                $request['user_id']= Auth::id();    
                SocialMedia::create($request->all());
                return $this->jsonResponseWithoutMessage("Your Account Deleted Successfully", 'data', 200);
        }
    }
        }
    
