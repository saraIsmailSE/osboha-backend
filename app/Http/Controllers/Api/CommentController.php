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
use App\Models\Post;
use App\Models\PostType;
use App\Models\Thesis;
use App\Models\ThesisType;
use App\Models\Week;
use App\Rules\base64OrImage;
use App\Rules\base64OrImageMaxSize;
use App\Traits\PathTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    use ResponseJson, MediaTraits, ThesisTraits, PathTrait;
    /**
     * Add a new comment or reply to the system.
     * Detailed Steps:
     *  1- Validate required data and the image format.
     *  2- Add a new comment or reply to the system.
     *  3- Add the image to the system using MediaTraits if the request has an image.
     *  4- There is two type for thesis :
     *      - Thesis has a body.
     *      - Thesis has an image.
     * If the thesis has an image (Add the image to the system using MediaTraits).
     *  5- Add a new thesis to the system if the comment type is “thesis”. 
     *  6- Return a success or error message.
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            //body is required only if the comment is not a thesis and has no image            
            //in case of read only, body and screenshot are not required
            'body' => [
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type != "thesis" && !$request->has('image') && !$request->has('body')) {
                        $fail('The body field is required.');
                    }
                },
            ],
            'book_id' => 'required_without:post_id|numeric',
            'post_id' => 'required_without:book_id|numeric',
            'comment_id' => 'numeric',
            'type' => 'required',
            //image is required only if the comment is not a thesis and has no body
            'image' => [
                new base64OrImage(),
                new base64OrImageMaxSize(2 * 1024 * 1024),
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type != "thesis" && !$request->has('body') && !$request->has('image')) {
                        $fail('The image field is required.');
                    }
                },
            ],
            //screenshots have to be an array of images
            'screenShots' => 'array',
            'screenShots.*' => [new base64OrImage(), new base64OrImageMaxSize(2 * 1024 * 1024)],
            'start_page' => 'required_if:type,thesis|numeric',
            'end_page' => 'required_if:type,thesis|numeric',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $input = $request->all();
        $input['user_id'] = Auth::id();
        if (!$request->has('post_id')) {
            $input['post_id'] = Post::where('book_id', $request->book_id)->where('type_id', PostType::where('type', 'book')->first()->id)->first()->id;
        }

        $post = Post::find($input['post_id']);

        if ($post->allow_comments == 0) {
            return $this->jsonResponseWithoutMessage('Comments are not allowed on this post', 'data', 500);
        }

        //start transaction - asmaa (to be able to rollback in case of an error)
        DB::beginTransaction();

        try {
            $comment = Comment::create($input);

            if ($request->type == "thesis") {
                // $book = Post::find($request->post_id)->book;
                $book = Book::find($request->book_id);
                $thesis['comment_id'] = $comment->id;
                $thesis['book_id'] = $book->id;
                $thesis['start_page'] = $request->start_page;
                $thesis['end_page'] = $request->end_page;
                $thesis['type_id'] = ThesisType::where('type', $book->type->type)->first()->id;
                if ($request->has('body')) {
                    // $thesis['max_length'] = strlen(trim($request->body)); //getting the length wrong
                    $thesis['max_length'] = Str::length(trim($request->body));
                }
                /**asmaa **/
                if ($request->has('screenShots') && count($request->screenShots) > 0) {
                    $total_screenshots = count($request->screenShots);
                    $thesis['total_screenshots'] = $total_screenshots;

                    //if the comment has no body, the first screenshot will be added as a comment
                    //the rest will be added as replies with new comment created for each of them

                    //theses/book_id/user_id/
                    $folder_path = 'theses/' . $book->id . '/' . Auth::id();

                    //check if theses folder exists
                    if (!file_exists(public_path('assets/images/' . $folder_path))) {
                        mkdir(public_path('assets/images/' . $folder_path), 0777, true);
                    }

                    if (!$request->has('body')) {
                        $this->createMedia($request->screenShots[0], $comment->id, 'comment', $folder_path);
                        $comment->type = 'screenshot';
                        $comment->save();
                        for ($i = 1; $i < $total_screenshots; $i++) {
                            $media_comment = Comment::create([
                                'user_id' => Auth::id(),
                                'post_id' => $input['post_id'],
                                'comment_id' => $comment->id,
                                'type' => 'screenshot',
                            ]);
                            $this->createMedia($request->screenShots[$i], $media_comment->id, 'comment', $folder_path);
                        }
                    } else {

                        //if the comment has a body, the screenshots will be added as replies with new comment created for each of them
                        for ($i = 0; $i < $total_screenshots; $i++) {
                            $media_comment = Comment::create([
                                'user_id' => Auth::id(),
                                'post_id' => $input['post_id'],
                                'comment_id' => $comment->id,
                                'type' => 'screenshot',
                            ]);
                            $this->createMedia($request->screenShots[$i], $media_comment->id, 'comment', $folder_path);
                        }
                    }
                }
                /**asmaa **/
                $this->createThesis($thesis);
                $comment->load(['replies', 'thesis', 'user.userBooks' => function ($query) use ($book) {
                    $query->where('book_id', $book->id);
                }]);
            }

            if ($request->has('image')) {
                // if comment has media
                // upload media
                $folder_path = 'comments/' . Auth::id();
                if (!file_exists(public_path('assets/images/' . $folder_path))) {
                    mkdir(public_path('assets/images/' . $folder_path), 0777, true);
                }

                $this->createMedia($request->image, $comment->id, 'comment', $folder_path);
            }

            DB::commit();

            $message = null;
            $reciever_id = null;
            if ($comment->type === 'normal' &&  Auth::id() !== $post->user_id) {
                $message = 'لقد قام ' . Auth::user()->name . " بالتعليق على منشورك";
                $reciever_id = $post->user_id;
            } else if ($comment->type === 'reply') {
                $parentComment = Comment::find($request->comment_id);
                if ($parentComment && $parentComment->user_id !== Auth::id()) {

                    $message = 'لقد قام ' . Auth::user()->name . " بالرد على تعليقك";
                    $reciever_id = $parentComment->user_id;
                }
            }

            //notify the creator
            if ($message && $reciever_id) {
                (new NotificationController)->sendNotification($reciever_id, $message, USER_POSTS, $this->getPostPath($post->id));
            }

            return $this->jsonResponseWithoutMessage(new CommentResource($comment), 'data', 200);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
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
        $comments = Comment::where('post_id', $post_id)
            ->whereHas('user', function ($query) use ($user_id) {
                if ($user_id) {
                    $query->where('id', $user_id);
                }
            })
            ->where('comment_id', 0)
            ->with('reactions', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        if ($comments->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'comments' => CommentResource::collection($comments),
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
                        if ($request->has('body')) {
                            $thesis['max_length'] = Str::length(trim($request->body));
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

                    DB::commit();
                    return $this->jsonResponseWithoutMessage(new CommentResource($comment), 'data', 200);
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
     * @todo 1- Check if the comment exists.
     * @todo 2- Check if the logged in user has the “delete comment” permission or the logged in user_id has to match the user_id in the request.
     * @todo 3- Delete the comment replies.
     * @todo 4- If comment type is “thesis” delete the thesis screenshots.
     * @todo 5- Delete the comment screenshots.
     * @todo 6- Delete the thesis.
     * @todo 7- Delete the comment media.
     * @todo 8- Delete the comment.     
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
                    if ($comment->type == "thesis" || $comment->type == "screenshot") {

                        $currentWeek = Week::latest()->first();
                        $thesis = Thesis::where('comment_id', $comment->id)->first();

                        if ($thesis->mark->week_id < $currentWeek->id) {
                            return $this->jsonResponseWithoutMessage('لقد انتهى الوقت, لا يمكنك حذف الأطروحة', 'data', 500);
                        }

                        $thesis['comment_id'] = $comment->id;
                        /**asmaa */
                        //delete the screenshots
                        $screenshots_comments = Comment::where('comment_id', $comment->id)->orWhere('id', $comment->id)->where('type', 'screenshot')->get();
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
                    $comment->reactions()->delete();

                    //delete replies
                    $replies = Comment::where('comment_id', $comment->id);

                    //delete replies reactions
                    $replies->each(function ($reply) {
                        $reply->reactions()->delete();

                        //delete replies media
                        if ($reply->media) {
                            $this->deleteMedia($reply->media->id);
                        }

                        //delete replies
                        $reply->delete();
                    });

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
}