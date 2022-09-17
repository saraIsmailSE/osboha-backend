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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\CommentResource;
use App\Http\Controllers\Api\ThesisController;
use App\Models\Book;

class CommentController extends Controller
{
    use ResponseJson , MediaTraits , ThesisTraits;
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
    public function create(Request $request){

        $validator = Validator::make($request->all(), [
            'body' => 'required_without_all:image,screenShots|string',
            'post_id' => 'required|numeric',
            'comment_id' => 'numeric',
            'type' => 'required',
            'image' => 'required_without_all:body,screenShots|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'screenShots' => 'array|required_if:type,thesis|required_without_all:image,body',
            'total_pages' => 'required_if:type,thesis|numeric',
            'thesis_type_id' => 'required_if:type,thesis|numeric',

        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $input = $request->all();
        $input['user_id'] = Auth::id();
        $comment = Comment::create($input);

        if ($request->type == "thesis") {
            $thesis['comment_id'] = $comment->id;
            $thesis['book_id'] = Book::where('post_id', $request->post_id)->pluck('id')[0];
            $thesis['total_pages'] = $request->total_pages;
            $thesis['type_id'] = $request->thesis_type_id;
            if ($request->has('body')) {
                $thesis['max_length'] = strlen($request->body);
            }
            if ($request->has('screenShots')) {
                $thesis['total_screenshots'] = count($request->screenShots);
                foreach ($request->screenShots as $screenShot) {
                    $this->createMedia($screenShot, $comment->id, 'comment');
                }
            }
            $this->createThesis($thesis);
        }

        if ($request->hasFile('image')) {
            // if comment has media
            // upload media
            $this->createMedia($request->file('image'), $comment->id, 'comment');
        }

        return $this->jsonResponseWithoutMessage("Comment Created Successfully", 'data', 200);
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
            'body' => 'required_without_all:image,screenShots|string',
            'comment_id' => 'numeric',
            'image' => 'required_without_all:body,screenShots|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'screenShots' => 'array|required_if:type,thesis|required_without_all:image,body',
            'total_pages' => 'required_if:type,thesis|numeric',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $comment = Comment::find($request->comment_id);
        if ($comment) {
            if (Auth::id() == $comment->user_id) {
                if ($comment->type == "thesis") {
                    $thesis['comment_id'] = $comment->id;
                    $thesis['book_id'] = Book::where('post_id', $request->post_id)->pluck('id')[0];
                    $thesis['total_pages'] = $request->total_pages;
                    $thesis['thesis_type_id'] = $request->thesis_type_id;
                    if ($request->has('body')) {
                        $thesis['max_length'] = strlen($request->body);
                    }
                    if ($request->has('screenShots')) {
                        $thesis['total_screenshots'] = count($request->screenShots);
                        foreach ($request->screenShots as $screenShot) {
                            $this->createMedia($screenShot, $comment->id, 'comment');
                        }
                    }
                    $this->updateThesis($thesis);
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
                    $this->deleteThesis($thesis);
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