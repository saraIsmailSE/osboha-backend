<?php

namespace App\Observers;

use App\Models\User;
use App\Models\UserGroup;
use App\Notifications\MailExcludeAmbassador;

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
        //
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        if ($user->is_excluded == 1) {
            $userGroups = UserGroup::where('user_id', $user->id)->get();

            foreach ($userGroups as $user) {
                $user->termination_reason = 'مستبعد';
                $user->save();
            }

            foreach ($user->roles as $role) {
                if ($role->name !== 'ambassador') {
                    $user->removeRole($role->name);
                }
            }

            $user->notify(new MailExcludeAmbassador());
        }
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        //
    }

    /**
     * Handle the User "restored" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function restored(User $user)
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        //
    }
}