<?php

namespace App\Http\Middleware;

use App\Models\SocialMedia;
use App\Models\UserGroup;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $group = UserGroup::where('user_id', $user->id)->where('user_type', 'ambassador')->whereNull('termination_reason')->first();
        $have_full_name = $user->name != "" && str_replace(' ', '', $user->last_name);
        $not_have_any_social_media  = SocialMedia::where('user_id', $user->id)->whereNull('facebook')->whereNull('instagram')->whereNull('whatsapp')->whereNull('telegram')->first();;
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
        } else if (!$have_full_name || $not_have_any_social_media) {
            $response  = [
                'success' => false,
                'data' => 'should update info'
            ];
            return response()->json($response, 400);
        }
        return $next($request);
    }
}
