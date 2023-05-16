<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\Mark;
use App\Models\Media;
use App\Traits\MediaTraits;

class MarkObserver
{
    use MediaTraits;
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
        if ($mark->reading_mark == 0 && $mark->writing_mark == 0) {
            $theses = $mark->thesis();

            foreach ($theses as $thesis) {
                $thesisComment = $thesis->comment;
                $screenshotsComments = Comment::where('comment_id', $thesisComment->id)->orWhere('id', $thesisComment->id)->where('type', 'screenshot')->get();
                $media = Media::whereIn('comment_id', $screenshotsComments->pluck('id'))->get();

                foreach ($media as $item) {
                    $this->deleteMedia($item->id);
                }

                //delete replies
                $thesisComment->replies()->each(function ($reply) {
                    //delete media
                    $media = Media::where('comment_id', $reply->id)->first();
                    if ($media) {
                        $this->deleteMedia($media->id);
                    }

                    $reply->delete();
                });

                $thesisComment->delete();
                $thesis->delete();
                $screenshotsComments->each(function ($screenshot) {
                    $screenshot->delete();
                });
            }
        }
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