<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reaction;
use App\Models\Media;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\ReactionResource;
use App\Http\Resources\ReactionTypeResource;
use App\Models\ReactionType;

class ReactionController extends Controller
{
    use ResponseJson, MediaTraits;
    /**
     * Return all reactions found in the system by the auth user.
     * 
     * @return jsonResponseWithoutMessage
     */
    public function index()
    {
        $reactions = Reaction::where('user_id', Auth::id())->get();
        if ($reactions->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(ReactionResource::collection($reactions), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Add new reaction to the system.
     * 
     * Detailed Steps:
     *  1- Validate required data and the image format.
     *  2- If reaction_id != 0 ; user can add new reaction to the post or the comment.
     *  3- Else if reaction_id == 0 ; user have permission to add new reaction to the system.
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function create(Request $request)
    {
        ####Rufi####
        //validate requested data
        $validator = Validator::make($request->all(), [
            'reaction_id' => 'required_without_all:media,type',
            'post_id'     => 'required_without_all:comment_id,media,type',
            'comment_id'  => 'required_without_all:post_id,media,type',
            'media'       => 'required_if:reaction_id,==,0',
            'type'        => 'required_if:reaction_id,==,0',
        ]);
        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        // if reaction_id != 0 ; user add reaction to post or comment
        if ($request['reaction_id'] != 0) {
            //create new reaction
            $reaction = Reaction::create([
                'user_id'     => Auth::id(),
                'reaction_id' => $request->reaction_id,
                'post_id'     => $request->post_id,
                'comment_id'  => $request->comment_id,
            ]);
            //success response after creating the reaction
            return $this->jsonResponse(new ReactionResource($reaction), 'data', 200, 'Reaction Created Successfully');
        }
        // else if reaction_id == 0 ; user have permission to add new reaction 
        else {
            //authorized user
            if (Auth::user()->can('create reaction')) {
                //create new reaction
                $reaction = Reaction::create([
                    'reaction_id' => 0,
                    'user_id' => Auth::id(),
                ]);
                //upload media
                $this->createMedia($request->file('media'), $reaction->id, 'reaction');
                //success response after creating the reaction
                return $this->jsonResponse(new ReactionResource($reaction), 'data', 200, 'New Reaction Created Successfully');
            } else {
                //unauthorized user response
                throw new NotAuthorized;
            }
        }
    }
    /**
     * Update an existing reaction in the system.
     * 
     * Detailed Steps:
     *  1- Validate required data and the image format.
     *  2- If request has media;user have permission to update existing media of reaction in the system.
     *  3- Else if request doesn't has media;user can update existing reaction in the post or comment in system by the auth user.
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function update(Request $request)
    {
        ####Rufi####
        //validate requested data
        $validator = Validator::make($request->all(), [
            'reaction_id'  => 'required',
            'comment_id'   => 'required_without_all:post_id,media',
            'post_id'      => 'required_without_all:comment_id,media',
            'media'        => 'required_without_all:post_id,comment_id',
        ]);
        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        //******To edit media of reaction by user have permission to edit******//
        if ($request->has('media')) {
            //authorized user
            if (Auth::user()->can('edit reaction')) {
                //find media belong to reaction
                $media = Media::where('reaction_id', $request->reaction_id)->first();
                //update found media
                $reaction = $this->updateMedia($request->file('media'), $media->id);
                //success response after update
                return $this->jsonResponse(new ReactionResource($reaction), 'data', 200, 'Reaction Updated Successfully');
            } else {
                //unauthorized user response
                throw new NotAuthorized;
            }
        }
        //******To edit reaction of post or comment******//
        else {
            //find reaction belong to auth user and comment
            if ($request->has('comment_id'))
                $reaction = Reaction::where('user_id', Auth::id())->where('comment_id', $request->comment_id)->first();
            //find reaction belong to auth user and comment
            else if ($request->has('post_id'))
                $reaction = Reaction::where('user_id', Auth::id())->where('post_id', $request->post_id)->first();
            //find media belong to user have a unauthorized to edit media of reaction
            if ($reaction) {
                //update found reaction
                $reaction->update($request->all());
                //success response after update
                return $this->jsonResponse(new ReactionResource($reaction), 'data', 200, 'Reaction Updated Successfully');
            } else {
                //not fount reaction exception
                throw new NotFound;;
            }
        }
    }
    /**
     * Find an existing reaction in the system by comment id or post id and display it.
     * 
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function show(Request $request)
    {
        ####Rufi####
        //validate requested data
        $validator = Validator::make($request->all(), [
            'comment_id' => 'required_without:post_id',
            'post_id' => 'required_without:comment_id',
        ]);
        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        //find reaction belong to auth user and comment
        if ($request->has('comment_id'))
            $reaction = Reaction::where('comment_id', $request->comment_id)->get();
        //find reaction belong to auth user and post
        else if ($request->has('post_id'))
            $reaction = Reaction::where('post_id', $request->post_id)->get();
        if ($reaction->isNotEmpty()) {
            //return found reaction
            return $this->jsonResponseWithoutMessage(ReactionResource::collection($reaction), 'data', 200);
        } else {
            //reaction not found response
            throw new NotFound;
        }
    }
    /**
     * Delete an existing reaction in the system.
     * 
     * Detailed Steps:
     *  1- Validate required data and the image format.
     *  2- If request has comment_id or post_id;user can delete existing reaction in the post or the comment in the system by the auth user.
     *  3- Else ;user have permission to delete existing media of reaction in the system.
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function delete(Request $request)
    {
        ####Rufi####
        //validate requested data
        $validator = Validator::make($request->all(), [
            'reaction_id'  => 'required_without_all:post_id,comment_id',
            'comment_id'   => 'required_without_all:post_id,reaction_id',
            'post_id'      => 'required_without_all:comment_id,reaction_id',

        ]);
        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        //******To delete reaction of post or comment******//
        //find reaction belong to auth user and comment
        if ($request->has('comment_id'))
            $reaction = Reaction::where('user_id', Auth::id())->where('comment_id', $request->comment_id)->first();
        //find reaction belong to auth user and comment
        else if ($request->has('post_id'))
            $reaction = Reaction::where('user_id', Auth::id())->where('post_id', $request->post_id)->first();
        //**************To delete reaction*****************//
        else {
            //authorized user
            if (Auth::user()->can('delete reaction')) {
                //find reaction
                $reaction = Reaction::where('id', $request->reaction_id)->get();
                if ($reaction->isNotEmpty()) {
                    //update found medias
                    foreach ($reaction as $row)
                        $row->update(['reaction_id' => 1]);
                    //find media belong to reaction
                    $media = Media::where('reaction_id', $request->reaction_id)->first();
                    //delete found media
                    $this->deleteMedia($media->id);
                    $reaction = Reaction::find($request->reaction_id);
                } else {
                    //not found reaction exception
                    throw new NotFound;
                }
            } else {
                //unauthorized user response
                throw new NotAuthorized;
            }
        }
        if ($reaction) {
            //delete found reaction
            $reaction->delete();
            //success response after update
            return $this->jsonResponse(new ReactionResource($reaction), 'data', 200, 'Reaction Deleted Successfully');
        } else {
            //not found reaction exception
            throw new NotFound;
        }
    }

    /**
     * get all reaction types in the system.
     * @return jsonResponse;     
     */
    public function getReactionTypes()
    {
        $reactions = ReactionType::where('is_active', 1)->with('media')->get();

        if ($reactions->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(ReactionTypeResource::collection($reactions), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}