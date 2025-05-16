<?php

namespace App\Services;

use App\Models\Post;
use App\Traits\MediaTraits;
use Illuminate\Support\Facades\DB;

class PostService
{
    use MediaTraits;

    public function deletePost(Post $post): bool
    {
        DB::beginTransaction();

        try {
            // $currentMedia = $post->media;
            // if ($currentMedia->isNotEmpty()) {
            //     foreach ($currentMedia as $media) {
            //         $this->deleteMedia($media->id, 'posts/' . $post->user_id);
            //     }
            // }

            $post->media->each(function ($media) use ($post) {
                $this->deleteMedia($media->id, 'posts/' . $post->user_id);
            });


            $post->taggedUsers->each(function ($tag) {
                $tag->delete();
            });

            // $tags = $post->taggedUsers;
            // if ($tags->isNotEmpty()) {
            //     foreach ($tags as $tag) {
            //         $tag->delete();
            //     }
            // }

            $post->reactions()->detach();

            $post->comments->each(function ($comment) {
                $comment->reactions()->detach();

                $media = $comment->media;
                if ($media) {
                    $this->deleteMedia($media->id);
                }

                $comment->delete();
            });

            $post->article->delete();
            $post->activity->delete();

            $post->rates->each(function ($rate) {
                $rate->delete();
            });

            $post->pollVotes()->each(function ($vote) {
                $vote->delete();
            });

            $post->pollOptions->each(function ($option) {
                $option->delete();
            });

            $post->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return false;
        }
    }
}
