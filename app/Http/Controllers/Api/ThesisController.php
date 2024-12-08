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
use App\Traits\ThesisTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ThesisController extends Controller
{
    use ResponseJson, ThesisTraits;
    /**
     * Find an existing thesis in the system by its id and display it.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage ;
     */
    public function show($thesis_id)
    {

        $thesis = Thesis::with('book')->with('comment')->with('mark.week')->with('modifiedTheses')->find($thesis_id);


        if ($thesis) {
            $totalPages = $this->getTotalThesisPages($thesis->start_page, $thesis->end_page);
            $thesis->max_allowed_parts = $this->getMaxAllowedThesisWritingParts($totalPages, $thesis->max_length, $thesis->total_screenshots);

            return $this->jsonResponseWithoutMessage($thesis, 'data', 200);
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
            ->with('thesis')
            ->orderBy('created_at', 'desc')->paginate(10);

        if ($comments->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(
                [
                    // 'theses' => CommentResource::collection($comments),

                    'theses' => $comments->items(),
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
            ->withCount('reactions')
            ->with('thesis')
            ->orderBy('created_at', 'desc')->paginate(10);

        if ($comments->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(
                [
                    // 'theses' => CommentResource::collection($comments),
                    'theses' => $comments->items(),
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
            ->withCount('reactions')
            ->orderBy('created_at', 'desc')->paginate(10);

        if ($theses->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                // 'theses' => CommentResource::collection($theses),
                'theses' => $theses->items(),
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
            ->with('thesis')
            ->orderBy('created_at', 'desc')->get();

        if ($theses->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage($theses, 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    public function checkThesisOverlap(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'start_page' => 'required|numeric',
            'end_page' => 'required|numeric',
            'book_id' => 'required|exists:books,id',
            'thesis_id' => 'nullable|exists:theses,id',
        ], [
            'start_page.required' => 'صفحة البداية مطلوبة',
            'end_page.required' => 'صفحة النهاية مطلوبة',
            'start_page.numeric' => 'صفحة البداية يجب ان تكون رقمية',
            'end_page.numeric' => 'صفحة النهاية يجب ان تكون رقمية',
            'book_id.required' => 'رقم الكتاب مطلوب',
            'book_id.exists' => 'الكتاب غير موجود',
            'thesis_id.exists' => 'الأطروحة غير موجودة',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors()->first(), 'error', 400);
        }

        $user_id = Auth::id();
        if ($this->checkOverlap($request->start_page, $request->end_page, $request->book_id, $user_id, $request->thesis_id)) {
            return $this->jsonResponseWithoutMessage([
                "overlap" => true,
            ], 'data', 200);
        }

        return $this->jsonResponseWithoutMessage([
            "overlap" => false,
        ], 'data', 200);
    }
}
