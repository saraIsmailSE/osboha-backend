<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\NotFound;
use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Http\Resources\ThesisResource;
use App\Models\Comment;
use App\Models\Mark;
use App\Models\Post;
use App\Models\PostType;
use App\Models\Thesis;
use App\Models\ThesisType;
use App\Models\Week;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\This;

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
     * Get all the theses related to a requested book.
     * 
     * @param  Integer  $book_id
     * @return jsonResponseWithoutMessage ;
     */
    public function listBookThesis($book_id)
    {
        $post_id = Post::where('book_id', $book_id)->where('type_id', PostType::where('type', 'book')->first()->id)->first()->id;
        $comments = Comment::where('post_id', $post_id)
            ->where('comment_id', 0)
            ->with('reactions', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->withCount('reactions')
            ->with('thesis')->orderBy('created_at', 'desc')->paginate(10);

        if ($comments->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(
                [
                    'theses' => CommentResource::collection($comments),
                    'total' => $comments->total(),
                ],
                'data',
                200
            );
        } else {
            throw new NotFound;
        }
    }
    /**
     * Get all the theses related to a requested user.
     * 
     * @param  Integer  $user_id
     * @return jsonResponseWithoutMessage ;
     */
    public function listUserThesis($user_id)
    {
        $theses = Comment::where('user_id', $user_id)->where('type', 'thesis')->where('comment_id', 0)->with('thesis')->orderBy('created_at', 'desc')->get();

        if ($theses->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(CommentResource::collection($theses), 'data', 200);
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
        $theses = Comment::where('type', 'thesis')->where('comment_id', 0)->whereHas('thesis.mark', function ($query) use ($week_id) {
            $query->where('week_id', $week_id);
        })->with('thesis')->orderBy('created_at', 'desc')->get();

        if ($theses->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(CommentResource::collection($theses), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}