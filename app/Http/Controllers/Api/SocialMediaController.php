<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use App\Models\SocialMedia;

class SocialMediaController extends Controller
{
    use ResponseJson;
    
    public function index()
    {
        $socialMedia = SocialMedia::all();
        if($socialMedia){
            return $this->jsonResponseWithoutMessage($socialMedia, 'data',200);
        }
    }

    public function create(Request $request)
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

        SocialMedia::create($request->all());
        return $this->jsonResponseWithoutMessage("Your Accounts Are Added Successfully", 'data', 200);
        
    }

    
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'socialMedia_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $socialMedia = SocialMedia::find($request->socialMedia_id);
        if($socialMedia){
            return $this->jsonResponseWithoutMessage($socialMedia, 'data',200);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'socialMedia_id' => 'required',
            'facebook' => 'required_without_all:twitter,instagram',
            'twitter' => 'required_without_all:facebook,instagram',
            'instagram' => 'required_without_all:facebook,twitter',

        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $socialMedia = SocialMedia::find($request->socialMedia_id);
        $socialMedia->update($request->all());
        return $this->jsonResponseWithoutMessage("Your Accounts Are Updated Successfully", 'data', 200);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'socialMedia_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $socialMedia = SocialMedia::find($request->socialMedia_id);
        $socialMedia->delete();
        return $this->jsonResponseWithoutMessage("Your Accounts Are Deleted Successfully", 'data', 200);
    }
}
