<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Http\Resources\ThesisResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostType;
use App\Models\Thesis;
use App\Models\Week;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThesisController extends Controller
{
    use ResponseJson;
    /**
     * Find an existing thesis in the system by its id and display it.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage ;
     */
    public function show($thesis_id)
    {

        $thesis = Thesis::with('comment')->with('mark.week')->find($thesis_id);

        if ($thesis) {
            return $this->jsonResponseWithoutMessage(new ThesisResource($thesis), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Get all the theses related to a requested book and a requested user.
     * 
     * @param  Integer  $book_id
     * @param  Integer  $user_id
     * @return jsonResponseWithoutMessage ;
     */
    public function listBookThesis($book_id, $user_id = null)
    {
        $post_id = Post::where('book_id', $book_id)->where('type_id', PostType::where('type', 'book')->first()->id)->first()->id;
        $comments = Comment::where('post_id', $post_id)
            ->where('comment_id', 0)
            ->whereHas('user', function ($query) use ($user_id) {
                if ($user_id) {
                    $query->where('id', $user_id);
                }
            })
            ->with('reactions', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->withCount('reactions')
            ->with('thesis')
            ->orderBy('created_at', 'desc')->paginate(10);

        if ($comments->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(
                [
                    'theses' => CommentResource::collection($comments),
                    'total' => $comments->total(),
                ],
                'data',
                200
            );
        }
        return $this->jsonResponseWithoutMessage(
            [
                'theses' => [],
                'total' => 0,
            ],
            'data',
            200
        );
    }

    /**
     * Get all the theses related to a requested book and a requested thesis.
     * @param Integer  $book_id
     * @param Integer  $thesis_id
     * @return jsonResponseWithoutMessage ;
     */
    public function getBookThesis($book_id, $thesis_id)
    {
        $post_id = Post::where('book_id', $book_id)->where('type_id', PostType::where('type', 'book')->first()->id)->first()->id;
        $comments = Comment::where('post_id', $post_id)
            ->where('comment_id', 0)
            ->whereHas('thesis', function ($query) use ($thesis_id) {
                if ($thesis_id) {
                    $query->where('id', $thesis_id);
                }
            })
            ->with('reactions', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->withCount('reactions')
            ->with('thesis')
            ->orderBy('created_at', 'desc')->paginate(10);

        if ($comments->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(
                [
                    'theses' => CommentResource::collection($comments),
                    'total' => $comments->total(),
                ],
                'data',
                200
            );
        }
        return $this->jsonResponseWithoutMessage(
            [
                'theses' => [],
                'total' => 0,
            ],
            'data',
            200
        );
    }
    /**
     * Get all the theses related to a requested user.
     * 
     * @param  Integer  $user_id
     * @return jsonResponseWithoutMessage ;
     */
    public function listUserThesis($user_id)
    {
        $theses = Comment::where('user_id', $user_id)
            ->where('type', 'thesis')
            ->where('comment_id', 0)
            ->with('thesis')
            ->with('reactions', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->withCount('reactions')
            ->orderBy('created_at', 'desc')->paginate(10);

        if ($theses->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'theses' => CommentResource::collection($theses),
                'total' => $theses->total(),
            ], 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Get all the theses related to a requested week.
     * 
     * @param  Integer $week_id
     * @return jsonResponseWithoutMessage ;
     */
    public function listWeekThesis($week_id)
    {
        $theses = Comment::where('type', 'thesis')
            ->where('comment_id', 0)
            ->whereHas('thesis.mark', function ($query) use ($week_id) {
                $query->where('week_id', $week_id);
            })
            ->with('replies')
            ->with('thesis')
            ->orderBy('created_at', 'desc')->get();

        if ($theses->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(CommentResource::collection($theses), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}