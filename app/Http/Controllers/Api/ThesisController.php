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

        $thesis = Thesis::with('comment')->find($thesis_id);

        if ($thesis) {
            return $this->jsonResponseWithoutMessage(new ThesisResource($thesis), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Get all the theses related to a requested book.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage ;
     */
    public function list_book_thesis(Request $request)
    {
        $validator = Validator::make($request->all(), ['book_id' => 'required']);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $post_id = Post::where('book_id', $request->book_id)->where('type_id', PostType::where('type', 'book')->first()->id)->first()->id;
        $comments = Comment::where('post_id', $post_id)
            ->where('comment_id', 0)
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
     * @param  Request  $request
     * @return jsonResponseWithoutMessage ;
     */
    public function list_user_thesis(Request $request)
    {
        $validator = Validator::make($request->all(), ['user_id' => 'required']);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //select function to be added in order to reduce the data retrieved
        $thesis = Thesis::join('comments', 'comments.id', '=', 'theses.comment_id')
            ->leftJoin('media', 'comments.id', '=', 'media.comment_id')
            ->where('theses.user_id', $request->user_id)
            ->get();

        if ($thesis->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(ThesisResource::collection($thesis), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Get all the theses related to a requested week.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage ;
     */
    public function list_week_thesis(Request $request)
    {
        $validator = Validator::make($request->all(), ['week_id' => 'required']);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        //select function to be added in order to reduce the data retrieved
        $thesis = Thesis::select('theses.*')
            ->join('marks', 'marks.id', '=', 'theses.mark_id')
            ->join('weeks', 'weeks.id', '=', 'marks.week_id')
            ->join('comments', 'comments.id', '=', 'theses.comment_id')
            ->leftJoin('media', 'comments.id', '=', 'media.comment_id')
            ->where('marks.week_id', $request->week_id)
            ->get();

        if ($thesis->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(ThesisResource::collection($thesis), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}