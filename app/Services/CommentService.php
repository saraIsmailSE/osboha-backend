<?php

namespace App\Services;

use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Requests\CommentCreateRequest;
use App\Http\Requests\CommentUpdateRequest;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Week;
use App\Traits\CommentTrait;
use Illuminate\Support\Facades\Auth;

class CommentService
{
    use CommentTrait;

    public function createComment(CommentCreateRequest $request): Comment
    {
        $input = $request->validated();
        $input['user_id'] = Auth::id();
        $input['post_id'] = $this->getPostIdFromRequest($request);

        $post = Post::findOrFail($input['post_id']);

        $comment = Comment::create($input);

        if ($request->type === 'thesis') {
            $thesis = $this->handleThesisCreate($request, $comment, $input['post_id']);
            $this->createThesis($thesis);
        }

        if ($request->has('image')) {
            $folderPath = 'comments/' . Auth::id();
            $this->createMedia($request->image, $comment->id, 'comment', $folderPath);
        }

        $this->notifyAddComment($comment, $post, $request);

        $comment = $comment->fresh();

        if (in_array($comment->type, ['thesis', 'screenshot'])) {
            $comment->load([
                'thesis',
                'user.userBooks' => fn($query) => $query->where('book_id', $request->book_id)
            ]);
        }

        return $comment;
    }

    public function UpdateComment(CommentUpdateRequest $request): Comment
    {
        $request->validated();
        $comment = Comment::find($request->u_comment_id);

        if (!$comment) {
            abort(404, 'Comment not found');
        }

        if (Auth::id() !== $comment->user_id) {
            abort(403, 'You are not authorized to update this comment');
        }

        if ($comment->type === 'thesis' || $comment->type === 'screenshot') {
            if (!$request->has('start_page')) {
                abort(500, 'رقم الصفحة الأولى مطلوب');
            }

            if (!$request->has('end_page')) {
                abort(500, 'رقم الصفحة الأخيرة مطلوب');
            }
        } else {
            if ((!$request->has('body') || !$request->filled('body')) && (!$request->hasFile('image'))) {
                abort(500, 'النص مطلوب في حالة عدم وجود صورة');
            }
        }

        $input = $request->only(['body', 'image']);
        $isThesis = $comment->type === 'thesis' || $comment->type === 'screenshot';

        if ($isThesis) {
            if (!$request->has('body') && $request->has('screenShots')) {
                $input['type'] = 'screenshot';
            }

            $thesis = $this->handleThesisUpdate($request, $comment);
            $this->updateThesis($thesis);
        }

        if ($request->has('image')) {
            $this->handleCommentImageUpdate($request, $comment);
        }

        if (!$request->has('body')) {
            $input['body'] = null;
        }

        $comment->update($input);
        $comment->load('replies');

        if ($isThesis) {
            $comment->load('thesis');
        }

        return $comment;
    }

    public function deleteComment(int $comment_id): void
    {
        $comment = Comment::find($comment_id);
        if (!$comment) {
            abort(404, 'Comment not found');
        }

        $user = Auth::user();

        if (!$user->can('delete comment') && $user->id !== $comment->user_id && !$user->hasRole('admin')) {
            abort(403, 'You are not authorized to delete this comment');
        }

        $currentWeek = Week::latest()->first();

        $this->handleFridayThesis($comment, $currentWeek);

        $data = $this->handleThesisOrScreenshotDelete($comment, $currentWeek);

        if ($data) {
            if ($data['thesis']) {
                $this->deleteThesis($data['thesis']);
            }

            if ($data['screenshots']) {
                $data['screenshots']->each->delete();
            }
        }

        $this->deleteCommentMedia($comment);
        $this->deleteCommentReplies($comment);
        $this->deleteCommentRelations($comment);

        $comment->delete();
    }
}
