<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use App\Http\Resources\socialMediaResource;
use App\Models\SocialMedia;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use Illuminate\Support\Facades\Cache;

class SocialMediaController extends Controller
{
    use ResponseJson;

    /**
     * create user`s social media record or update exsisting one..
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage ;
     */
    public function addSocialMedia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'facebook' => 'required_without_all:whatsapp,instagram,telegram',
            'whatsapp' => 'required_without_all:facebook,instagram,telegram',
            'instagram' => 'required_without_all:facebook,whatsapp,telegram',
            'telegram' => 'required_without_all:facebook,whatsapp,instagram',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $authID=Auth::id();
        $socialAccounts = SocialMedia::updateOrCreate(
            ['user_id' => $authID],
            [
                'facebook' => $request->get('facebook'),
                'whatsapp' => $request->get('whatsapp'),
                'instagram' => $request->get('instagram'),
                'telegram' => $request->get('telegram')
            ]
        );

        $socialMediaCacheKey = 'social_media_' . $authID;
        Cache::forget($socialMediaCacheKey);

        return $this->jsonResponseWithoutMessage("Your Accounts Added Successfully", 'data', 200);
    }

    /**
     * Show user`s social media accounts.
     *
     * @param  $user_id
     * @return App\Http\Resources\socialMediaResource ;
     */
    public function show($user_id)
    {
        $socialMedia = SocialMedia::where('user_id', $user_id)->first();
        if ($socialMedia) {
            return $this->jsonResponseWithoutMessage(new socialMediaResource($socialMedia), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}
