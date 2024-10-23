<?php

namespace App\Http\Middleware;

use App\Models\SocialMedia;
use App\Models\UserGroup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class IsActiveUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('api');

        // Cache keys
        $userGroupCacheKey = 'user_group_' . $user->id;
        $socialMediaCacheKey = 'social_media_' . $user->id;

        $group = Cache::remember($userGroupCacheKey, 60, function () use ($user) {
            return UserGroup::where('user_id', $user->id)
                ->where('user_type', 'ambassador')
                ->whereNull('termination_reason')
                ->first();
        });

        $have_full_name = $user->name != "" && str_replace(' ', '', $user->last_name);

        $social_media_record = Cache::remember($socialMediaCacheKey, 60, function () use ($user) {
            return SocialMedia::where('user_id', $user->id)->first();
        });

        $have_any_social_media = false;

        if ($social_media_record) {
            $have_any_social_media = $social_media_record->facebook || $social_media_record->instagram || $social_media_record->whatsapp || $social_media_record->telegram;
        }

        if ($user->is_excluded == 1) {
            $response  = [
                'success' => false,
                'data' => 'excluded ambassador'
            ];
            return response()->json($response, 400);
        } else if ($user->is_hold == 1) {
            $response  = [
                'success' => false,
                'data' => 'withdrawn ambassador'
            ];
            return response()->json($response, 400);
        } else if (is_null($user->parent_id) || (!$group && !$user->hasRole('admin'))) {
            $response  = [
                'success' => false,
                'data' => 'ambassador without group'
            ];
            return response()->json($response, 400);
        } else if (!$have_full_name || !$social_media_record || !$have_any_social_media) {
            $response  = [
                'success' => false,
                'data' => 'should update info'
            ];
            return response()->json($response, 400);
        }
        return $next($request);
    }
}
