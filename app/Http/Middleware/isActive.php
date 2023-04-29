<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class IsActive
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


            if(is_null($user->parent_id)){
            $response  = [
                'success' => false,
                'data' => 'ambassador without group'
            ];
            return response()->json($response,400);
        }
        else if($user->is_excluded	== 1 ){
            $response  = [
                'success' => false,
                'data' => 'excluded ambassador'
            ];
            return response()->json($response,400);
        }
        return $next($request);
    }
}
