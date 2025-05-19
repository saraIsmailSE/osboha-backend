<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Media;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use App\Traits\ThesisTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Requests\CommentCreateRequest;
use App\Http\Requests\CommentUpdateRequest;
use App\Http\Resources\UserInfoResource;
use App\Models\Book;
use App\Models\Mark;
use App\Models\Post;
use App\Models\PostType;
use App\Models\Thesis;
use App\Models\ThesisType;
use App\Models\userWeekActivities;
use App\Models\Week;
use App\Rules\base64OrImage;
use App\Rules\base64OrImageMaxSize;
use App\Services\CommentService;
use App\Traits\PathTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommentController extends Controller
{
    use ResponseJson, MediaTraits, ThesisTraits, PathTrait;

    public function __construct(protected CommentService $commentService) {}


    public function create(CommentCreateRequest $request)
    {
        DB::beginTransaction();

        try {
            $comment = $this->commentService->createComment($request);
            DB::commit();
            return $this->jsonResponseWithoutMessage($comment, 'data', 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::channel('Comments')->error($e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    public function update(CommentUpdateRequest $request)
    {
        DB::beginTransaction();
        try {
            $comment = $this->commentService->UpdateComment($request);
            DB::commit();
            return $this->jsonResponseWithoutMessage($comment, 'data', 200);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    public function delete($comment_id)
    {
        DB::beginTransaction();
        try {
            $this->commentService->deleteComment($comment_id);
            DB::commit();
            return $this->jsonResponseWithoutMessage("Comment Deleted Successfully", 'data', 200);
        } catch (\Exception $e) {
            DB::rollback();
            $user_id = Auth::id();
            Log::channel('Comments')->error("Failed to delete comment ID: {$comment_id}, User ID: {$user_id} - {$e->getMessage()}", ['trace' => $e->getTrace()]);
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
