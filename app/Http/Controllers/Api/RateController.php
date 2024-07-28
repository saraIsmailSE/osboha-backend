<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rate;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use Spatie\Permission\PermissionRegistrar;
use App\Http\Resources\RateResource;
use App\Models\Book;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostType;
use Illuminate\Support\Facades\DB;

class RateController extends Controller
{
    use ResponseJson;
    /**
     * Return all rates found in the system by the auth user.
     * 
     * @return jsonResponseWithoutMessage
     */
    public function index()
    {
        $rates = Rate::where('user_id', Auth::id())->get();
        if ($rates) {
            return $this->jsonResponseWithoutMessage(RateResource::collection($rates), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Add new rate to the system.
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            //if the rate is for the post
            'post_id' => 'exists:posts,id',
            'book_id' => 'exists:books,id',
            'rate' => 'required|min:1|max:5',
            //if the rate is for the comment
            'comment_id' => 'exists:comments,id',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors()->first(), 'data', 500);
        }

        $post_id = $request->post_id;
        if ($request->has('post_id') || $request->has('book_id')) {
            $post = $request->has('post_id') ?
                Post::find($request->post_id) :
                Post::where('book_id', $request->book_id)->where('type_id', PostType::where('type', 'book_review')->first()->id)->first();
            $post_id = $request->post_id ?? $post->id;

            $userBook = $post->book->userBooks->where('user_id', Auth::id())->first();
            if (!$userBook || ($userBook->status != 'finished' && $userBook->counter <= 0)) {
                return $this->jsonResponseWithoutMessage("لا يمكنك تقييم الكتاب لأنك لم تنهي قراءته بعد!", 'data', 500);
            }
        }

        //check if user rated the post or the comment before
        $ratedBefore = Rate::where('user_id', Auth::id())->where(function ($q) use ($post_id, $request) {
            $q->where('post_id', $post_id)
                ->orWhere(function ($comment) use ($request) {
                    $comment->whereNotNull('comment_id')->where('comment_id', $request->comment_id);
                });
        })->exists();

        if ($ratedBefore) {
            return $this->jsonResponseWithoutMessage("تم التقييم مسبقاً!", 'data', 500);
        }

        DB::beginTransaction();

        try {

            $comment = Comment::create([
                'user_id' => Auth::id(),
                'post_id' => $post_id,
                'body' => $request->body,
                'type' => 'review',
            ]);

            // dd($comment->id);
            Rate::create([
                'user_id' => Auth::id(),
                'post_id' => $post_id,
                'comment_id' => $request->comment_id,
                'related_comment_id' => $comment->id,
                'rate' => $request->rate,
            ]);

            DB::commit();

            $comment = $comment->fresh();
            $comment->load(['rate']);

            return $this->jsonResponseWithoutMessage($comment, 'data', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }
    /**
     * Find an existing rate in the system by comment id or post id and display it.
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function show($rateId)
    {
        $rate = Rate::find($rateId);
        if ($rate) {
            $comment = $rate->relatedComment()->with('rate')->first();
            return $this->jsonResponseWithoutMessage($comment, 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Update an existing rate in the system by the auth user.
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function update(Request $request, $rateId)
    {
        $validator = Validator::make($request->all(), [
            'rate' => 'required|required|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors()->first(), 'data', 500);
        }

        $rate = Rate::find($rateId);

        if ($rate) {
            $rate->update(['rate' => $request->rate]);

            if ($request->has('body')) {
                $rate->relatedComment()->update(['body' => $request->body]);
            }

            $comment = $rate->relatedComment()->with('rate')->first();

            return $this->jsonResponseWithoutMessage($comment, 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Delete an existing rate in the system by the auth user.
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required_without:post_id',
            'post_id' => 'required_without:comment_id',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if ($request->has('comment_id'))
            $rate = Rate::where('user_id', Auth::id())->where('comment_id', $request->comment_id)->first();
        else if ($request->has('post_id'))
            $rate = Rate::where('user_id', Auth::id())->where('post_id', $request->post_id)->first();
        if ($rate) {
            $rate->delete();
            return $this->jsonResponseWithoutMessage("Rate Deleted Successfully", 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    public function getBookRates($bookId)
    {
        $book = Book::with('posts')->where('id', $bookId)->first();
        if ($book) {
            $book_review_post = $book->posts->where('type_id', PostType::where('type', 'book_review')->first()->id)->first();
            if ($book_review_post) {
                $rates = Comment::where('post_id', $book_review_post->id)
                    ->where('comment_id', 0)
                    ->withCount('reactions')
                    ->with('rate')
                    ->orderBy('created_at', 'desc')->paginate(10);

                if ($rates->isNotEmpty()) {
                    return $this->jsonResponseWithoutMessage(
                        [
                            'rates' => $rates->items(),
                            'total' => $rates->total(),
                        ],
                        'data',
                        200
                    );
                }
                return $this->jsonResponseWithoutMessage(
                    [
                        'rates' => [],
                        'total' => 0,
                    ],
                    'data',
                    200
                );
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotFound;
        }
    }
}
