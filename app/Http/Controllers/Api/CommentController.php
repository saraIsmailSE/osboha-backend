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
use App\Models\Book;
use App\Models\Post;
use App\Models\PostType;
use App\Models\Thesis;
use App\Models\ThesisType;
use Illuminate\Support\Str;

class CommentController extends Controller
{
    use ResponseJson, MediaTraits, ThesisTraits;
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
                'image',
                'mimes:jpeg,png,jpg,gif,svg|max:2048',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type != "thesis" && !$request->has('body') && !$request->has('image')) {
                        $fail('The image field is required.');
                    }
                },
            ],
            //screenshots have to be an array of images
            'screenShots' => 'array',
            'screenShots.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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
                if (!$request->has('body')) {
                    $this->createMedia($request->screenShots[0], $comment->id, 'comment');
                    for ($i = 1; $i < $total_screenshots; $i++) {
                        $media_comment = Comment::create([
                            'user_id' => Auth::id(),
                            'post_id' => $input['post_id'],
                            'comment_id' => $comment->id,
                            'type' => 'screenshot',
                        ]);
                        $this->createMedia($request->screenShots[$i], $media_comment->id, 'comment');
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
                        $this->createMedia($request->screenShots[$i], $media_comment->id, 'comment');
                    }
                }
            }
            /**asmaa **/
            $this->createThesis($thesis);
            $comment->load('thesis');
        }

        if ($request->hasFile('image')) {
            // if comment has media
            // upload media
            $this->createMedia($request->file('image'), $comment->id, 'comment');
        }

        return $this->jsonResponseWithoutMessage(new CommentResource($comment), 'data', 200);
    }
    /**
     * Find and show an existing article in the system by its id.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function getPostComments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $comments = Comment::where('post_id', $request->post_id)->get();
        if ($comments->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(CommentResource::collection($comments), 'data', 200);
        } else {
            throw new NotFound;
        }
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
            // 'body' => 'required_without_all:image,screenShots|string',
            // 'comment_id' => 'numeric',
            // 'image' => 'required_without_all:body,screenShots|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            // 'screenShots' => 'array|required_if:type,thesis|required_without_all:image,body',
            // 'start_page' => 'required_if:type,thesis|numeric',
            // 'end_page' => 'required_if:type,thesis|numeric',

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


            'comment_id' => 'numeric|required',
            //image is required only if the comment is not a thesis and has no body
            'image' => [
                'image',
                'mimes:jpeg,png,jpg,gif,svg|max:2048',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->type != "thesis" && !$request->has('body') && !$request->has('image')) {
                        $fail('The image field is required.');
                    }
                },
            ],
            //screenshots have to be an array of images
            'screenShots' => 'array',
            'screenShots.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'start_page' => 'required_if:type,thesis|numeric',
            'end_page' => 'required_if:type,thesis|numeric',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $comment = Comment::find($request->comment_id);
        if ($comment) {
            if (Auth::id() == $comment->user_id) {
                if ($comment->type == "thesis") {
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
                    $screenshots_comments = Comment::where('comment_id', $comment->id)->orWhere('id', $comment->id)->where('type', 'screenshot')->get();
                    $media = Media::whereIn('comment_id', $screenshots_comments->pluck('id'))->get();
                    foreach ($media as $media_item) {
                        $this->deleteMedia($media_item->id);
                    }
                    $screenshots_comments->each->delete();

                    if ($request->has('screenShots') && count($request->screenShots) > 0) {
                        $total_screenshots = count($request->screenShots);
                        $thesis['total_screenshots'] = $total_screenshots;

                        //if the comment has no body, the first screenshot will be added as a comment
                        //the rest will be added as replies with new comment created for each of them
                        if (!$request->has('body')) {
                            $this->createMedia($request->screenShots[0], $comment->id, 'comment');
                            for ($i = 1; $i < $total_screenshots; $i++) {
                                $media_comment = Comment::create([
                                    'user_id' => Auth::id(),
                                    'post_id' => $comment->post_id,
                                    'comment_id' => $comment->id,
                                    'type' => 'screenshot',
                                ]);
                                $this->createMedia($request->screenShots[$i], $media_comment->id, 'comment');
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
                                $this->createMedia($request->screenShots[$i], $media_comment->id, 'comment');
                            }
                        }
                    }
                    /**asmaa **/
                    $comment->update($request->all());
                    return $this->updateThesis($thesis);
                }

                if ($request->hasFile('image')) {
                    // if comment has media
                    //check Media
                    $currentMedia = Media::where('comment_id', $comment->id)->first();
                    // if exists, update
                    if ($currentMedia) {
                        $this->updateMedia($request->file('image'), $currentMedia->id);
                    }
                    //else create new one
                    else {
                        // upload media
                        $this->createMedia($request->file('image'), $comment->id, 'comment');
                    }
                }
                $comment->update($request->all());

                return $this->jsonResponseWithoutMessage("Comment Updated Successfully", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }
    /**
     * Delete an existing comment using its id(“delete comment” permission is required).
     * In order to comment the Comment, the logged in user_id has to match the user_id in the request.
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
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $comment = Comment::find($request->comment_id);
        if ($comment) {
            if (Auth::user()->can('delete comment') || Auth::id() == $comment->user_id) {
                //delete replies
                Comment::where('comment_id', $comment->id)->delete();
                if ($comment->type == "thesis") {
                    $thesis['comment_id'] = $comment->id;
                    /**asmaa */
                    //delete the screenshots
                    $screenshots_comments = Comment::where('comment_id', $comment->id)->orWhere('id', $comment->id)->where('type', 'screenshot')->get();
                    $media = Media::whereIn('comment_id', $screenshots_comments->pluck('id'))->get();
                    foreach ($media as $media_item) {
                        $this->deleteMedia($media_item->id);
                    }
                    $screenshots_comments->each->delete();

                    /**asmaa */
                    return $this->deleteThesis($thesis);
                }
                //check Media
                $currentMedia = Media::where('comment_id', $comment->id)->first();
                // if exist, delete
                if ($currentMedia) {
                    $this->deleteMedia($currentMedia->id);
                }
                $comment->delete();
                return $this->jsonResponseWithoutMessage("Comment Deleted Successfully", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }
}