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


    #########UPDATE COMMENT########

    public function handleThesisUpdate(CommentUpdateRequest $request, Comment $comment): Thesis
    {
        $thesis = Thesis::where('comment_id', $comment->id)->first();

        if (!$thesis) {
            abort(500, 'الأطروحة غير موجودة');
        }

        $thesis->comment_id = $comment->id;
        $thesis->book_id = $comment->book_id;
        $thesis->start_page = $request->start_page;
        $thesis->end_page = $request->end_page;
        $thesis->status = 'pending';
        $thesis->max_length = $request->has('body') ? Str::length(trim($request->body)) : 0;
        $thesis->total_screenshots = $request->has('screenShots') ? count($request->screenShots) : 0;

        $this->deletePreviousScreenshots($comment);
        $this->handleScreenshotsUpdate($request, $comment, $thesis);

        return $thesis;
    }


    protected function handleCommentImageUpdate(CommentUpdateRequest $request, Comment $comment)
    {
        $currentMedia = Media::where('comment_id', $comment->id)->first();

        if ($currentMedia) {
            $this->updateMedia($request->image, $currentMedia->id, 'comments/' . Auth::id());
        } else {
            $this->createMedia($request->image, $comment->id, 'comment', 'comments/' . Auth::id());
        }
    }

    ########DELETE COMMENT########

    protected function handleFridayThesis(Comment $comment, Week $currentWeek): void
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
            $this->deleteMediaByMedia($media);
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
}
