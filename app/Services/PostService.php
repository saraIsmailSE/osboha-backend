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
            $currentMedia = $post->media;
            if ($currentMedia->isNotEmpty()) {
                foreach ($currentMedia as $media) {
                    $this->deleteMedia($media->id, 'posts/' . $post->user_id);
                }
            }

            $tags = $post->taggedUsers;
            if ($tags->isNotEmpty()) {
                foreach ($tags as $tag) {
                    $tag->delete();
                }
            }

            $post->reactions()->detach();

            $post->comments->each(function ($comment) {
                $comment->reactions()->detach();

                $media = $comment->media;
                if ($media) {
                    $this->deleteMedia($media->id);
                }

                $comment->delete();
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
