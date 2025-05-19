<?php

namespace App\Traits;

use App\Http\Controllers\Api\NotificationController;
use App\Http\Requests\CommentCreateRequest;
use App\Models\Book;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

trait CommentTrait
{
    use MediaTraits, ThesisTraits, PathTrait;

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

    public function handleThesisComment(CommentCreateRequest $request, Comment $comment, int $postId): void
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
            $this->handleScreenshots($request, $comment, $postId, $book);
        }

        $this->createThesis($thesis);
    }


    private function handleScreenshots(CommentCreateRequest $request, Comment $comment, int $postId, Book $book): void
    {
        $folderPath = 'theses/' . $book->id . '/' . Auth::id();

        foreach ($request->screenShots as $index => $screenshot) {
            if ($index === 0 && !$request->has('body')) {
                $comment->update(['type' => 'screenshot']);
                $mediaComment = $comment;
            } else {
                $mediaComment = $this->createScreenshotComment($comment->id, $postId);
            }

            $this->createMedia($screenshot, $mediaComment->id, 'comment', $folderPath);
        }
    }

    private function createScreenshotComment(int $commentId, int $postId): Comment
    {
        return Comment::create([
            'user_id' => Auth::id(),
            'post_id' => $postId,
            'comment_id' => $commentId,
            'type' => 'screenshot',
        ]);
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
}
