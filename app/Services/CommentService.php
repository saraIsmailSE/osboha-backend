<?php

namespace App\Services;

use App\Http\Controllers\Api\NotificationController;
use App\Http\Requests\CommentCreateRequest;
use App\Models\Book;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostType;
use App\Models\ThesisType;
use App\Traits\CommentTrait;
use App\Traits\MediaTraits;
use App\Traits\PathTrait;
use App\Traits\ThesisTraits;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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
            $this->handleThesisComment($request, $comment, $input['post_id']);
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
}
