<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CommentCreateRequest;
use App\Http\Requests\CommentUpdateRequest;
use App\Http\Resources\UserInfoResource;
use App\Services\CommentService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    use ResponseJson;

    public function __construct(protected CommentService $commentService) {}


    public function create(CommentCreateRequest $request)
    {
        DB::beginTransaction();
        try {
            $comment = $this->commentService->createComment($request);
            // DB::listen(function ($query) {
            //     Log::channel('Comments')->info("Queries executed: {$query->sql}", [
            //         'bindings' => $query->bindings,
            //     ]);
            // });
            // throw new Exception("fake exception");
            DB::commit();

            $this->commentService->clearImages();

            return $this->jsonResponseWithoutMessage($comment, 'data', 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::channel('Comments')->error("Failed to create comment. Error: {$e->getMessage()}", [
                'user_id' => Auth::id(),
                'post_id' => $request->post_id,
                'book_id' => $request->book_id,
            ]);
            Log::channel('Comments')->error("Trace: {$e->getTraceAsString()}");

            // if ($this->commentService->getAddedImages()) {
            //     Log::channel('Comments')->info("Deleting images from tmp path due to create comment error: ", $this->commentService->getAddedImages());
            //     $this->commentService->deleteUploadedImages();
            // }

            $this->commentService->clearTmpMedia();

            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    public function update(CommentUpdateRequest $request)
    {
        DB::beginTransaction();
        try {
            $comment = $this->commentService->UpdateComment($request);

            // throw new Exception("fake exception");
            DB::commit();

            $this->commentService->clearImages();
            return $this->jsonResponseWithoutMessage($comment, 'data', 200);
        } catch (\Throwable $e) {
            DB::rollback();

            // if($this->commentService->getUpdatedImagesPaths()) {
            //     Log::channel('Comments')->info("Restoring images due to update comment error: ", $this->commentService->getUpdatedImagesPaths());
            //     $this->commentService->restoreCommentImages();
            // }
            // if($this->commentService->getAddedImages()) {
            //     Log::channel('Comments')->info("Deleting images due to update comment error: ", $this->commentService->getAddedImages());
            //     $this->commentService->deleteUploadedImages();
            // }

            Log::channel('Comments')->error("Failed to update comment. Error: {$e->getMessage()}", [
                'user_id' => Auth::id(),
                'comment_id' => $request->u_comment_id,

            ]);
            Log::channel('Comments')->error("Trace: {$e->getTraceAsString()}");

            $this->commentService->clearTmpMedia();
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    public function delete($comment_id)
    {
        DB::beginTransaction();
        try {
            $this->commentService->deleteComment($comment_id);

            // throw new Exception("fake exception");
            DB::commit();

            $this->commentService->clearImages();
            return $this->jsonResponseWithoutMessage("Comment Deleted Successfully", 'data', 200);
        } catch (\Throwable $e) {
            DB::rollback();
            Log::channel('Comments')->error("Failed to delete comment. Error: {$e->getMessage()}", [
                'user_id' => Auth::id(),
                'comment_id' => $comment_id,

            ]);
            Log::channel('Comments')->error("Trace: {$e->getTraceAsString()}");

            // if ($this->commentService->getDeletedImagesPaths()) {
            //     Log::channel('Comments')->info("Restoring deleted images due to an error while deleting comment: ", $this->commentService->getDeletedImagesPaths());
            //     $this->commentService->restoreCommentImages();
            // }
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    public function getPostComments($post_id, $user_id = null)
    {

        $comments = Comment::where('post_id', $post_id)
            ->when($user_id, function ($query, $user_id) {
                return $query->whereHas('user', function ($innerQuery) use ($user_id) {
                    $innerQuery->where('id', $user_id);
                });
            })
            ->where('comment_id', 0)
            ->with(['reactions' => function ($query) {
                $query->where('user_id', Auth::id());
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        if ($comments->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'comments' => $comments->items(),
                'last_page' => $comments->lastPage(),
            ], 'data', 200);
        }

        return $this->jsonResponseWithoutMessage([], 'data', 200);
    }

    public function getPostCommentsUsers($post_id)
    {
        //get the user related to the comment and remove the duplicates
        $users = Comment::where('post_id', $post_id)
            ->where('comment_id', 0)
            ->with('user')
            ->get()
            ->unique('user_id')
            ->map(function ($comment) {
                return $comment->user;
            });

        //get 10 users
        $limited = $users->take(10);

        return $this->jsonResponseWithoutMessage([
            'users' => UserInfoResource::collection($limited),
            'count' => $users->count(),
        ], 'data', 200);
    }
}
