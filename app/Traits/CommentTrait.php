<?php

namespace App\Traits;

use App\Http\Controllers\Api\NotificationController;
use App\Http\Requests\CommentCreateRequest;
use App\Http\Requests\CommentUpdateRequest;
use App\Models\Book;
use App\Models\Comment;
use App\Models\Mark;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostType;
use App\Models\Thesis;
use App\Models\userWeekActivities;
use App\Models\Week;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

trait CommentTrait
{
    use MediaTraits, ThesisTraits, PathTrait;

    ########CREATE COMMENT########
    public function getPostIdFromRequest(CommentCreateRequest $request): int|null
    {
        if ($request->has('post_id')) {
            return $request->post_id;
        } elseif ($request->has('book_id')) {
            return $this->getPostByBookId($request->book_id);
        }

        return null;
    }

    private function getPostByBookId($bookId): int
    {
        $bookPostTypeId = Cache::remember('book_post_type_id', now()->addWeek(), fn() =>
        PostType::firstWhere('type', 'book')->id);

        return Post::where('book_id', $bookId)
            ->where('type_id', $bookPostTypeId)
            ->firstOrFail()
            ->id;
    }


    public function notifyAddComment(Comment $comment, Post $post, CommentCreateRequest $request): void
    {
        $message = $receiverId = null;

        if ($comment->type === 'normal' && Auth::id() !== $post->user_id) {
            $message = 'لقد قام ' . Auth::user()->name . ' بالتعليق على منشورك';
            $receiverId = $post->user_id;
        } elseif ($comment->type === 'reply') {
            $parentComment = Comment::find($request->comment_id);
            if ($parentComment && $parentComment->user_id !== Auth::id()) {
                $message = 'لقد قام ' . Auth::user()->name . ' بالرد على تعليقك';
                $receiverId = $parentComment->user_id;
            }
        }

        if ($message && $receiverId) {
            (new NotificationController)->sendNotification(
                $receiverId,
                $message,
                USER_POSTS,
                $this->getPostPath($post->id)
            );
        }
    }

    protected function handleThesisCreate(CommentCreateRequest $request, Comment $comment, int $postId): array
    {
        $book = Book::findOrFail($request->book_id);
        $thesis = [
            'comment_id' => $comment->id,
            'book_id' => $book->id,
            'start_page' => $request->start_page,
            'end_page' => $request->end_page,
            'type_id' => $this->getThesisTypeIdFromBook($book)
        ];

        if ($request->has('body')) {
            $thesis['max_length'] = Str::length(trim($request->body));
        }

        if ($request->has('screenShots')) {
            $thesis['total_screenshots'] = count($request->screenShots);

            $this->handleScreenshotsCreate($request, $comment, $postId, $book->id);
        }

        return $thesis;
    }


    protected function handleScreenshotsCreate(CommentCreateRequest|CommentUpdateRequest $request, Comment $comment, int $postId, int $book_id): void
    {
        $folderPath = 'theses/' . $book_id . '/' . Auth::id();

        foreach ($request->screenShots as $index => $screenshot) {
            if ($index === 0 && !$request->has('body')) {
                $comment->update(['type' => 'screenshot']);
                $mediaComment = $comment;
            } else {
                $mediaComment = $this->createScreenshotComment($comment->id, $postId);
            }

            $media = $this->createTmpMedia($screenshot, $mediaComment->id, 'comment', $folderPath);

            if (property_exists($this, 'addedImages')) {
                $this->addedImages[] = $media->media;
            }
        }
    }

    protected function createScreenshotComment(int $commentId, int $postId): Comment
    {
        return Comment::create([
            'user_id' => Auth::id(),
            'post_id' => $postId,
            'comment_id' => $commentId,
            'type' => 'screenshot',
        ]);
    }

    public function storeCommentImage(CommentCreateRequest $request, Comment $comment): void
    {
        $folderPath = 'comments/' . Auth::id();
        $media = $this->createTmpMedia($request->image, $comment->id, 'comment', $folderPath);

        if (property_exists($this, 'addedImages')) {
            $this->addedImages[] = $media->media;
        }
    }
    #########UPDATE COMMENT########

    public function handleThesisUpdate(CommentUpdateRequest $request, Comment $comment): Thesis
    {
        $thesis = Thesis::where('comment_id', $comment->id)->first();

        if (!$thesis) {
            abort(500, 'الأطروحة غير موجودة');
        }

        $thesis->comment_id = $comment->id;
        $thesis->start_page = $request->start_page;
        $thesis->end_page = $request->end_page;
        $thesis->status = 'pending';
        $thesis->max_length = $request->has('body') ? Str::length(trim($request->body)) : 0;
        $thesis->total_screenshots = $request->has('screenShots') ? count($request->screenShots) : 0;

        $this->deleteCommentScreenshots($comment);
        $this->handleScreenshotsCreate($request, $comment, $comment->post_id, $thesis->book_id);

        return $thesis;
    }


    protected function handleCommentImageUpdate(CommentUpdateRequest $request, Comment $comment)
    {
        $currentMedia = Media::where('comment_id', $comment->id)->first();

        if ($currentMedia) {
            $oldPath = $currentMedia->media;
            if (property_exists($this, 'updatedImages')) {
                $fileContent = $this->getMediaContent($oldPath);
                if ($fileContent) {
                    $this->updatedImages[$oldPath] = $fileContent;
                }
            }

            $currentMedia->delete();

            DB::afterCommit(function () use ($oldPath) {
                $this->deleteMediaFile($oldPath);
            });

            // $media =  $this->updateMedia($request->image, $currentMedia->id, 'comments/' . Auth::id());
            if (property_exists($this, 'addedImages')) {
                $this->addedImages[] = $oldPath;
            }
        } else {
            $media =  $this->createTmpMedia($request->image, $comment->id, 'comment', 'comments/' . Auth::id());
            if (property_exists($this, 'addedImages')) {
                $this->addedImages[] = $media->media;
            }
        }
    }

    protected function deleteCommentScreenshots(Comment $comment)
    {
        /**asmaa **/
        //delete the previous screenshots
        //because the user can't edit the screenshots, so if user kept the screenshots and added new ones,
        //the old ones will be deleted and added again with the new ones
        //if the user deleted all screenshots, the old ones will be deleted

        $screenshots = Comment::where('type', 'screenshot')
            ->where(function ($q) use ($comment) {
                $q->where('comment_id', $comment->id)->orWhere('id', $comment->id);
            })->get();

        $filesToDelete = $screenshots->pluck('media.media')->toArray();
        Media::whereIn('comment_id', $screenshots->pluck('id'))->each(function ($media) {
            if (property_exists($this, 'updatedImages')) {
                $fileContent = $this->getMediaContent($media->media);
                if ($fileContent) {
                    $this->deletedImages[$media->media] = $fileContent;
                }
            }
            // $this->deleteMediaByMedia($media);
            $media->delete();
        });

        $screenshots->each(function ($c) use ($comment) {
            if ($c->id !== $comment->id) {
                $c->delete();
            }
        });

        DB::afterCommit(function () use ($filesToDelete) {
            foreach ($filesToDelete as $file) {
                $this->deleteMediaFile($file);
            }
        });
    }

    ########DELETE COMMENT########

    protected function handleFridayThesisDelete(Comment $comment, Week $currentWeek): void
    {
        $post = Post::findOrFail($comment->post_id);

        if ($post && $post->type->type === 'friday-thesis') {
            $graded = userWeekActivities::where('user_id', $comment->user_id)
                ->where('week_id', $currentWeek->id)
                ->first();

            if ($graded && $graded->week_id < $currentWeek->id) {
                abort(500, 'لقد انتهى الوقت, لا يمكنك حذف مشاركتك');
            }

            if ($graded) {
                $mark = Mark::where('week_id', $currentWeek->id)
                    ->where('user_id', $comment->user_id)
                    ->first();
                if ($mark) {
                    $theses_mark = $this->calculateAllThesesMark($mark->id);
                    $mark->update(['writing_mark' => $theses_mark['writing_mark']]);
                }
                $graded->delete();
            }
        }
    }

    protected function deleteCommentMedia($comment)
    {
        $media = Media::where('comment_id', $comment->id)->first();
        if ($media) {
            if (property_exists($this, 'updatedImages')) {
                $fileContent = $this->getMediaContent($media->media);
                if ($fileContent) {
                    $this->deletedImages[$media->media] = $fileContent;
                }
            }
            // $this->deleteMediaByMedia($media);
            $file = $media->media;
            $media->delete();

            DB::afterCommit(function () use ($file) {
                $this->deleteMediaFile($file);
            });
        }
    }

    protected function deleteCommentReplies($comment)
    {
        $replies = Comment::where('comment_id', $comment->id)
            ->each(function ($reply) {
                $reply->reactions()->detach();

                if ($reply->media) {
                    $this->deleteMediaByMedia($reply->media);
                }

                $reply->delete();
            });
    }

    protected function deleteCommentRelations($comment)
    {
        $comment->reactions()?->detach();
        $comment->rate()?->delete();
        $comment->rates()?->delete();
    }

    protected function handleThesisDelete($comment, $currentWeek): void
    {
        if (!in_array($comment->type, ['thesis', 'screenshot'])) return;

        $commentId = $comment->type === 'thesis'
            ? $comment->id
            : ($comment->comment_id > 0 ? $comment->comment_id : $comment->id);

        $thesis = Thesis::where('comment_id', $commentId)->first();

        if (!$thesis || !$thesis->mark) {
            abort(500, 'الأطروحة غير موجودة');
        }

        if ($thesis && $thesis->mark && $thesis->mark->week_id < $currentWeek->id) {
            abort(500, 'لقد انتهى الوقت, لا يمكنك حذف الأطروحة');
        }

        // Delete screenshots and their media
        $screenshotComments = Comment::where(function ($query) use ($commentId) {
            $query->where('comment_id', $commentId)->orWhere('id', $commentId);
        })->where('type', 'screenshot')->get();

        $filesToDelete = $screenshotComments->pluck('media.media')->toArray();
        Media::whereIn('comment_id', $screenshotComments->pluck('id'))->each(function ($media) {
            if (property_exists($this, 'deletedImages')) {
                $fileContent = $this->getMediaContent($media->media);
                if ($fileContent) {
                    $this->deletedImages[$media->media] = $fileContent;
                }
            }
            // $this->deleteMediaByMedia($media);
            $media->delete();
        });

        $this->deleteThesis($thesis);

        $screenshotComments->each(function ($screenshot) {
            $screenshot->delete();
        });

        DB::afterCommit(function () use ($filesToDelete) {
            // dd("filesToDelete", $filesToDelete);
            foreach ($filesToDelete as $file) {
                $this->deleteMediaFile($file);
            }
        });
    }

    //methods for handling images backup and restoration
    public function deleteAddedImages(): void
    {
        foreach ($this->addedImages as $path) {
            $this->deleteMediaFile($path);
        }

        $this->addedImages = []; // Clear to avoid double deletes
    }

    protected function restoreImages(array $images): void
    {
        foreach ($images as $path => $fileContent) {
            $filePath = public_path('assets/images/' . $path);

            if (!File::exists($filePath)) {
                File::put($filePath, $fileContent);
            }
        }
    }

    public function restoreUpdatedImages(): void
    {
        if (empty($this->updatedImages)) {
            return; // No images to restore
        }

        $this->restoreImages($this->updatedImages);

        $this->updatedImages = []; // Clear to avoid double restores
    }

    public function restoreDeletedImages(): void
    {
        if (empty($this->deletedImages)) {
            return; // No images to restore
        }

        $this->restoreImages($this->deletedImages);

        $this->deletedImages = []; // Clear to avoid double restores
    }

    public function getImagesPaths(array $images): array
    {
        $paths = [];
        foreach ($images as $path => $content) {
            $paths[] = $path;
        }

        return $paths;
    }
}
