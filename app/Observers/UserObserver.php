<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserStatistic;
class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        $user_stat = UserStatistic::latest()->first();
        $user_stat->total_new_users +=  1;
        $user_stat->save();
        
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        $old_user = $user->getOriginal(); 
        $user_stat = UserStatistic::latest()->first();
        if($user['is_excluded'] == 1){
            $user_stat->total_excluded_users +=  1;
        }
        if($old_user['is_excluded'] == 1){
            if($user_stat->total_excluded_users !=0){
                $user_stat->total_excluded_users -=  1;

            }
        }
        $user_stat->save();
    }
}