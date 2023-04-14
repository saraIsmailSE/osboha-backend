<?php

namespace App\Observers;

use App\Models\Mark;

class MarkObserver
{
    /**
     * Handle the Mark "created" event.
     *
     * @param  \App\Models\Mark  $mark
     * @return void
     */
    public function created(Mark $mark)
    {
        //
    }

    /**
     * Handle the Mark "updated" event.
     *
     * @param  \App\Models\Mark  $mark
     * @return void
     */
    public function updated(Mark $mark)
    {
        //
    }

    /**
     * Handle the Mark "deleted" event.
     *
     * @param  \App\Models\Mark  $mark
     * @return void
     */
    public function deleted(Mark $mark)
    {
        //
    }

    /**
     * Handle the Mark "restored" event.
     *
     * @param  \App\Models\Mark  $mark
     * @return void
     */
    public function restored(Mark $mark)
    {
        //
    }

    /**
     * Handle the Mark "force deleted" event.
     *
     * @param  \App\Models\Mark  $mark
     * @return void
     */
    public function forceDeleted(Mark $mark)
    {
        //
    }
}
