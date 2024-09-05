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
use App\Http\Resources\CommentResource;
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
use App\Traits\PathTrait;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    use ResponseJson, MediaTraits, ThesisTraits, PathTrait;

    /**
     * Add a new comment to the system.
     * Two types of comments can be added:
     * 1- Normal comment: a comment that has a body and an image. (for posts mainly)
     * 2- Thesis comment: a comment that has a body or/and screenshots. (for books mainly)
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getCreateValidationRules($request), $this->getCreateValidationMessages());

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors()->first(), 'data', 500);
        }

        $input = $request->all();
        $input['user_id'] = Auth::id();
        $input['post_id'] = $this->getPostId($request);

        $post = Post::findOrFail($input['post_id']);

        DB::beginTransaction();
        try {
            $comment = Comment::create($input);

            if ($request->type == "thesis") {
                $this->handleThesisComment($request, $comment, $input['post_id']);
            }

            if ($request->has('image')) {
                $folder_path = 'comments/' . Auth::id();
                $this->createMedia($request->image, $comment->id, 'comment', $folder_path);
            }

            DB::commit();

            $this->notifyIfNeeded($comment, $post, $request);

            $comment = $comment->fresh();

            if (in_array($comment->type, ['thesis', 'screenshot'])) {
                $comment->load([
                    'thesis',
                    'user.userBooks' => function ($query) use ($request) {
                        $query->where('book_id', $request->book_id);
                    }
                ]);
            }

            return $this->jsonResponseWithoutMessage($comment, 'data', 200);
        } catch (\Exception $e) {
            Log::channel('books')->error($e->getMessage() . ' ' . $e->getTraceAsString());
            DB::rollback();
            return $this->jsonResponseWithoutMessage($e->getMessage() . ':' . $e->getLine(), 'data', 500);
        }
    }
    /**
     * Get all comments for a post.
     *
     * @param  int  $post_id
     * @return jsonResponseWithoutMessage;
     */
    public function getPostComments($post_id, $user_id = null)
    {
        // $comments = Comment::where('post_id', $post_id)
        //     ->whereHas('user', function ($query) use ($user_id) {
        //         if ($user_id) {
        //             $query->where('id', $user_id);
        //         }
        //     })
        //     ->where('comment_id', 0)
        //     ->with('reactions', function ($query) {
        //         $query->where('user_id', Auth::id());
        //     })
        //     ->orderBy('created_at', 'desc')
        //     ->paginate(10);

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
                // 'comments' => CommentResource::collection($comments),
                'comments' => $comments->items(),
                'last_page' => $comments->lastPage(),
            ], 'data', 200);
        }

        return $this->jsonResponseWithoutMessage([], 'data', 200);
    }

    /**
     * Get all users comments for a post.
     * @param int $post_id
     * @return jsonResponseWithoutMessage;
     */

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

    /**
     * Update an existing Comment’s details.
     * In order to update the Comment, the logged in user_id has to match the user_id in the request.
     * Detailed Steps:
     *  1- Validate required data and the image format.
     *  2- Find the requested comment by comment_id.
     *  3- Update the requested comment in the system if the logged in user_id has to match the user_id in the request.
     *  4- If comment type is “thesis” update the thesis.
     *  5- If the requested has image :
     *      -if image exists, update the image in the system using updateMedia.
     *      -else image doesn't exists, add the image to the system using MediaTraits
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'string',
            'u_comment_id' => 'required|numeric',
            'image' => [new base64OrImage(), new base64OrImageMaxSize(2 * 1024 * 1024)],
            'screenShots' => 'array',
            'screenShots.*' => [new base64OrImage(), new base64OrImageMaxSize(2 * 1024 * 1024)],
            'start_page' => 'numeric',
            'end_page' => 'numeric',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $input = $request->only(['body', 'image']);
        $comment = Comment::find($request->u_comment_id);

        if ($comment->type === 'thesis' || $comment->type === 'screenshot') {
            if (!$request->has('start_page') || !$request->start_page) {
                return $this->jsonResponseWithoutMessage('start page is required', 'data', 500);
            }

            if (!$request->has('end_page') || !$request->end_page) {
                return $this->jsonResponseWithoutMessage('end page is required', 'data', 500);
            }
        } else {
            if ((!$request->has('body') || !$request->body) && (!$request->has('image'))) {
                return $this->jsonResponseWithoutMessage('Body is required without image and vise versa', 'data', 500);
            }
        }

        if ($comment) {
            if (Auth::id() == $comment->user_id) {
                DB::beginTransaction();

                try {
                    if ($comment->type === "thesis" || $comment->type === "screenshot") {
                        $thesis = Thesis::where('comment_id', $comment->id)->first();
                        $thesis['comment_id'] = $comment->id;
                        $thesis['book_id'] = $thesis->book_id;
                        $thesis['start_page'] = $request->start_page;
                        $thesis['end_page'] = $request->end_page;
                        $thesis['status'] = 'pending';
                        if ($request->has('body')) {
                            $thesis['max_length'] = Str::length(trim($request->body));
                        } else {
                            $thesis['max_length'] = 0;
                        }
                        /**asmaa **/
                        //delete the previous screenshots
                        //because the user can't edit the screenshots, so if user kept the screenshots and added new ones,
                        //the old ones will be deleted and added again with the new ones
                        //if the user deleted all screenshots, the old ones will be deleted
                        $screenshots_comments =
                            Comment::where('type', 'screenshot')
                            ->where(function ($query) use ($comment) {
                                $query->where('comment_id', $comment->id)
                                    ->orWhere('id', $comment->id);
                            })
                            ->get();
                        $media = Media::whereIn('comment_id', $screenshots_comments->pluck('id'))->get();
                        foreach ($media as $media_item) {
                            $this->deleteMedia($media_item->id);
                        }

                        $screenshots_comments->each(function ($screenshot) use ($comment) {
                            if ($screenshot->id !== $comment->id) {
                                $screenshot->delete();
                            }
                        });

                        if ($request->has('screenShots') && count($request->screenShots) > 0) {
                            $total_screenshots = count($request->screenShots);
                            $thesis['total_screenshots'] = $total_screenshots;

                            $folder_path = 'theses/' . $thesis->book_id . '/' . Auth::id();
                            //if the comment has no body, the first screenshot will be added as a comment
                            //the rest will be added as replies with new comment created for each of them
                            if (!$request->has('body')) {
                                $this->createMedia($request->screenShots[0], $comment->id, 'comment',  $folder_path);
                                $input['type'] = 'screenshot';
                                for ($i = 1; $i < $total_screenshots; $i++) {
                                    $media_comment = Comment::create([
                                        'user_id' => Auth::id(),
                                        'post_id' => $comment->post_id,
                                        'comment_id' => $comment->id,
                                        'type' => 'screenshot',
                                    ]);
                                    $this->createMedia($request->screenShots[$i], $media_comment->id, 'comment',  $folder_path);
                                }
                            } else {
                                //if the comment has a body, the screenshots will be added as replies with new comment created for each of them
                                for ($i = 0; $i < $total_screenshots; $i++) {
                                    $media_comment = Comment::create([
                                        'user_id' => Auth::id(),
                                        'post_id' => $comment->post_id,
                                        'comment_id' => $comment->id,
                                        'type' => 'screenshot',
                                    ]);
                                    $this->createMedia($request->screenShots[$i], $media_comment->id, 'comment',  $folder_path);
                                }
                            }
                        } else {
                            $thesis['total_screenshots'] = 0;
                        }
                        $this->updateThesis($thesis);
                    }

                    if ($request->has('image')) {
                        // if comment has media
                        //check Media
                        $currentMedia = Media::where('comment_id', $comment->id)->first();
                        // if exists, update
                        if ($currentMedia) {
                            $this->updateMedia($request->image, $currentMedia->id, 'comments/' . Auth::id());
                        }
                        //else create new one
                        else {
                            // upload media
                            $this->createMedia($request->image, $comment->id, 'comment', 'comments/' . Auth::id());
                        }
                    }

                    if (!$request->has('body')) {
                        $input['body'] = null;
                    }

                    $comment->update($input);

                    $comment->load('replies');

                    if ($comment->type === 'thesis' || $comment->type === 'screenshot') {
                        $comment->load('thesis');
                    }

                    DB::commit();
                    return $this->jsonResponseWithoutMessage($comment, 'data', 200);
                } catch (\Exception $e) {
                    DB::rollback();
                    return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
                }
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }
    /**
     * Delete an existing comment using its id (“delete comment” permission is required).
     * 1- Check if the comment exists.
     * 2- Check if the logged in user has the “delete comment” permission or the logged in user_id has to match the user_id in the request.
     * 3- Delete the comment replies.
     * 4- If comment type is “thesis” delete the thesis screenshots.
     * 5- Delete the comment screenshots.
     * 6- Delete the thesis.
     * 7- Delete the comment media.
     * 8- Delete the comment.
     * @param  Int $comment_id
     * @return jsonResponse;
     */
    public function delete($comment_id)
    {
        $comment = Comment::find($comment_id);
        if ($comment) {
            if (Auth::user()->can('delete comment') || Auth::id() == $comment->user_id || Auth::user()->hasRole('admin')) {

                DB::beginTransaction();

                try {

                    $post = Post::find($comment->post_id);
                    if ($post->type->type == 'friday-thesis') {

                        $currentWeek = Week::latest()->first();

                        $graded = userWeekActivities::where('user_id', $comment->user_id)->where('week_id', $currentWeek->id)->first();
                        if ($graded) {
                            if ($graded->week_id < $currentWeek->id) {
                                return $this->jsonResponseWithoutMessage('لقد انتهى الوقت, لا يمكنك حذف مشاركتك', 'data', 500);
                            }

                            $mark = Mark::where('week_id', $currentWeek->id)->where('user_id', $comment->user_id)->first();
                            if ($mark) {
                                // calculate mark
                                $theses_mark = $this->calculateAllThesesMark($mark->id);
                                $writing_mark = $theses_mark['writing_mark'];
                                $mark->update(['writing_mark' => $writing_mark]);
                                $graded->delete();
                            }
                        }
                    }


                    if ($comment->type == "thesis" || $comment->type == "screenshot") {

                        $currentWeek = Week::latest()->first();

                        $commentId = $comment->type === 'thesis' ? $comment->id : ($comment->comment_id > 0 ? $comment->comment_id : $comment->id);
                        $thesis = Thesis::where('comment_id', $commentId)->first();

                        if ($thesis->mark->week_id < $currentWeek->id) {
                            return $this->jsonResponseWithoutMessage('لقد انتهى الوقت, لا يمكنك حذف الأطروحة', 'data', 500);
                        }

                        $thesis['comment_id'] = $commentId;
                        /**asmaa */
                        //delete the screenshots
                        $screenshots_comments = Comment::where('comment_id', $commentId)->orWhere('id', $commentId)->where('type', 'screenshot')->get();
                        $media = Media::whereIn('comment_id', $screenshots_comments->pluck('id'))->get();

                        foreach ($media as $media_item) {
                            $this->deleteMedia($media_item->id);
                        }

                        $this->deleteThesis($thesis);

                        $screenshots_comments->each(function ($screenshot) {
                            $screenshot->delete();
                        });
                    }

                    //check Media
                    $currentMedia = Media::where('comment_id', $comment->id)->first();
                    // if exist, delete
                    if ($currentMedia) {
                        $this->deleteMedia($currentMedia->id);
                    }

                    //delete reactions on this comment
                    $comment->reactions()->detach();

                    //delete replies
                    $replies = Comment::where('comment_id', $comment->id);

                    //delete replies reactions
                    $replies->each(function ($reply) {
                        $reply->reactions()->detach();

                        //delete replies media
                        if ($reply->media) {
                            $this->deleteMedia($reply->media->id);
                        }

                        //delete replies
                        $reply->delete();
                    });

                    //delete rates (related rates)
                    $comment->rate()->delete();

                    //delete rates (rates on this comment)
                    $comment->rates()->delete();

                    $comment->delete();

                    DB::commit();
                    return $this->jsonResponseWithoutMessage("Comment Deleted Successfully", 'data', 200);
                } catch (\Exception $e) {
                    DB::rollback();
                    return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
                }
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }

    private function getCreateValidationRules(Request $request): array
    {
        return [
            'body' => [
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type != "thesis" && !$request->has('image') && !$request->has('body')) {
                        $fail('النص مطلوب في حالة عدم وجود صورة');
                    }
                },
            ],
            'book_id' => 'required_without:post_id|numeric',
            'post_id' => 'required_without:book_id|numeric',
            'comment_id' => 'numeric',
            'type' => 'required',
            'image' => [
                new base64OrImage(),
                new base64OrImageMaxSize(2 * 1024 * 1024),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type != "thesis" && !$request->has('body') && !$request->has('image')) {
                        $fail('The image field is required.');
                    }
                },
            ],
            'screenShots' => 'array',
            'screenShots.*' => [new base64OrImage(), new base64OrImageMaxSize(2 * 1024 * 1024)],
            'start_page' => 'required_if:type,thesis|numeric',
            'end_page' => 'required_if:type,thesis|numeric',
        ];
    }

    private function getCreateValidationMessages(): array
    {
        return [
            'body.string' => 'النص يجب ان يكون نص',
            'book_id.required_without' => 'book_id مطلوب',
            'post_id.required_without' => 'post_id مطلوب',
            'type.required' => 'النوع مطلوب',
            'image.required' => 'الصورة مطلوبة',
            'screenShots.array' => 'السكرينات مطلوبة',
            'screenShots.*.base64_or_image' => 'الصورة يجب ان تكون من نوع صورة',
            'screenShots.*.base64_or_image_max_size' => 'الصورة يجب ان تكون اقل من 2 ميجا',
            'start_page.required_if' => 'الصفحة الأولى مطلوبة',
            'end_page.required_if' => 'الصفحة الأخيرة مطلوبة',
        ];
    }

    private function getPostId(Request $request): int
    {
        if (!$request->has('post_id')) {
            $bookPostTypeId  = Cache::remember('book_post_type_id', now()->addWeek(), function () {
                return PostType::firstWhere('type', 'book')->id;
            });

            return Post::where('book_id', $request->book_id)
                ->where('type_id', $bookPostTypeId)
                ->firstOrFail()
                ->id;
        }

        return $request->post_id;
    }

    private function handleThesisComment(Request $request, Comment $comment, int $postId): void
    {
        $book = Book::findOrFail($request->book_id);
        $thesis = [
            'comment_id' => $comment->id,
            'book_id' => $book->id,
            'start_page' => $request->start_page,
            'end_page' => $request->end_page,
            'type_id' => $this->getThesisTypeId($book)
        ];

        if ($request->has('body')) {
            $thesis['max_length'] = Str::length(trim($request->body));
        }

        if ($request->has('screenShots')) {
            $thesis['total_screenshots'] = count($request->screenShots);
            $this->handleScreenshots($request, $comment, $postId, $book);
        }

        $this->createThesis($thesis);
    }

    private function getThesisTypeId(Book $book): int
    {
        return ThesisType::where('type', $book->type->type == 'free' ? "normal" : $book->type->type)
            ->firstOrFail()
            ->id;
    }

    private function handleScreenshots(Request $request, Comment $comment, int $postId, Book $book): void
    {
        $folderPath = 'theses/' . $book->id . '/' . Auth::id();
        foreach ($request->screenShots as $index => $screenshot) {
            if ($index === 0 && !$request->has('body')) {
                $comment->type = 'screenshot';
                $comment->save();

                $mediaComment = $comment;
            } else {
                $mediaComment = $this->createScreenshotComment($comment->id, $postId);
            }

            $this->createMedia($screenshot, $mediaComment->id, 'comment', $folderPath);
        }
    }

    private function createScreenshotComment(int $commentId, int $postId): Comment
    {
        return Comment::create([
            'user_id' => Auth::id(),
            'post_id' => $postId,
            'comment_id' => $commentId,
            'type' => 'screenshot',
        ]);
    }

    private function notifyIfNeeded(Comment $comment, Post $post, Request $request): void
    {
        $message = $receiverId = null;

        if ($comment->type === 'normal' && Auth::id() !== $post->user_id) {
            $message = 'لقد قام ' . Auth::user()->name . " بالتعليق على منشورك";
            $receiverId = $post->user_id;
        } elseif ($comment->type === 'reply') {
            $parentComment = Comment::find($request->comment_id);
            if ($parentComment && $parentComment->user_id !== Auth::id()) {
                $message = 'لقد قام ' . Auth::user()->name . " بالرد على تعليقك";
                $receiverId = $parentComment->user_id;
            }
        }

        if ($message && $receiverId) {
            (new NotificationController)->sendNotification($receiverId, $message, USER_POSTS, $this->getPostPath($post->id));
        }
    }
}
