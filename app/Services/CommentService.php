<?php

namespace App\Services;

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

    protected array $addedImages = []; //for storing images added by creating or updating
    protected array $updatedImages = []; //for storing images deleted by updating
    protected array $deletedImages = []; //for storing deleted images paths during delete comment

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
            $media = $this->createMedia($request->image, $comment->id, 'comment', $folderPath);

            $this->addedImages[] = $media->media;
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

        $comment = $comment->fresh();
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

        if (
            !$user->can('delete comment') && $user->id !== $comment->user_id &&
            !$user->hasRole('admin')
        ) {
            abort(403, 'You are not authorized to delete this comment');
        }

        $currentWeek = Week::latest()->first();

        $this->handleFridayThesisDelete($comment, $currentWeek);

        $data = $this->handleThesisDelete($comment, $currentWeek);

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


    //properties and methods for handling images backup and restoration
    public function getAddedImages(): array
    {
        return $this->addedImages;
    }

    public function deleteUploadedImages(): void
    {
        $this->deleteAddedImages();
    }

    public function clearAddedImages(): void
    {
        $this->addedImages = [];
    }

    public function getUpdatedImagesPaths(): array
    {
        return $this->getImagesPaths($this->updatedImages);
    }

    public function clearUpdatedImages(): void
    {
        $this->updatedImages = [];
    }

    public function restoreDeletedImagesByUpdate(): void
    {
        $this->restoreUpdatedImages();
    }

    public function getDeletedImages(): array
    {
        return $this->deletedImages;
    }

    public function clearDeletedImages(): void
    {
        $this->deletedImages = [];
    }

    public function restoreDeletedImagesByDelete(): void
    {
        $this->restoreDeletedImages();
    }

    public function restoreCommentImages(): void
    {
        $this->restoreDeletedImagesByUpdate();
        $this->restoreDeletedImagesByDelete();
    }

    public function getDeletedImagesPaths(): array
    {
        return $this->getImagesPaths($this->deletedImages);
    }

    public function clearImages(): void
    {
        $this->clearAddedImages();
        $this->clearUpdatedImages();
        $this->clearDeletedImages();
    }
}
