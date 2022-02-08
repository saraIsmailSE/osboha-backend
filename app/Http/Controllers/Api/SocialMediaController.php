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
        return $socialMedia;
        if($socialMedia){
            return $this->jsonResponseWithoutMessage($socialMedia, 'data',200);
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        } 
        
        if ($request->facebook == null && $request->twitter == null && $request->instagram == null ) {
            return $this->jsonResponseWithoutMessage("Enter One Account at least", 'data', 500);
        }

        SocialMedia::create($request->all());
        return $this->jsonResponseWithoutMessage("Your Accounts are added Successfully", 'data', 200);
        
    }

    
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $socialMedia = SocialMedia::find($request->id);
        if($socialMedia){
            return $this->jsonResponseWithoutMessage($socialMedia, 'data',200);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'id' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if ($request->facebook == null && $request->twitter == null && $request->instagram == null ) {
            return $this->jsonResponseWithoutMessage("Enter One Account at least", 'data', 500);
        }

        $socialMedia = SocialMedia::find($request->id);
        $socialMedia->update($request->all());
        return $this->jsonResponseWithoutMessage("Your Accounts Are Updated Successfully", 'data', 200);
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $socialMedia = SocialMedia::find($request->id);
        $socialMedia->delete();
        return $this->jsonResponseWithoutMessage("Your Accounts Are Deleted Successfully", 'data', 200);
    }
}
