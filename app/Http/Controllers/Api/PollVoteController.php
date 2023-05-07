<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PollVote;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\PollVoteResource;
use App\Http\Resources\UserInfoResource;
use App\Models\Post;
use App\Models\User;


class PollVoteController extends Controller
{
    use ResponseJson;
    /**
     * Read all information about all votes in the system.
     * 
     * @return jsonResponseWithoutMessage
     */
    public function index()
    {
        $votes = PollVote::all();

        if ($votes->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(PollVoteResource::collection($votes), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Add a new vote to the system for the auth user.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'option_id' => 'required',
            'post_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $post = Post::find($request->post_id);
        if (!$post) {
            throw new NotFound;
        } else {
            if (!$post->is_approved) {
                return $this->jsonResponse(null, 'data', 500, "لا يمكنك التصويت على المنشور قبل قبوله");
            }
        }

        $vote = PollVote::where('user_id', Auth::id())->where('post_id', $request->post_id)->where('poll_option_id', $request->option_id)->first();
        if ($vote) {
            return $this->jsonResponseWithoutMessage(null, 'data', 200);
        }

        // Check if the user has already voted for this post
        $vote = PollVote::where('user_id', Auth::id())->where('post_id', $request->post_id)->first();

        if ($vote) {
            // Update the vote
            $vote->update([
                'poll_option_id' => $request->option_id
            ]);
            return $this->jsonResponseWithoutMessage("updated", 'data', 200);
        } else {
            // Create a new vote
            $vote = PollVote::create([
                'user_id' => Auth::id(),
                'post_id' => $request->post_id,
                'poll_option_id' => $request->option_id
            ]);
            return $this->jsonResponseWithoutMessage("created", 'data', 200);
        }
    }
    /**
     * Find existing vote in the system by its id.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'poll_vote_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $vote = PollVote::find($request->poll_vote_id);
        if ($vote) {
            return $this->jsonResponseWithoutMessage(new PollVoteResource($vote), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    /**
     * Update existing vote in the system by the auth user.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            //'user_id' => 'required',
            //'post_id' => 'required',
            'option' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $input = $request->all();
        $vote = PollVote::find($request->id);

        if ($vote) {
            if (Auth::id() == $vote->user_id) {
                $option = $request->option;
                $input['option'] = serialize($option);
                $vote->update($input);
                //$vote->update($request->all());

                return $this->jsonResponseWithoutMessage("Vote Updated Successfully", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }
    /**
     * Delete existing vote in the system by its id and by auth user only.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'poll_vote_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $vote = PollVote::find($request->poll_vote_id);
        if ($vote) {
            if (Auth::id() == $vote->user_id) {
                $vote->delete();
                return $this->jsonResponseWithoutMessage("Vote Deleted Successfully", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
        }
    }
    /**
     * Read all information about all votes that match requested post_id.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function votesByPostId(Request $request)
    {
        $post_id = $request->post_id;

        //find votes belong to post_id
        $votes = PollVote::where('post_id', $post_id)->get();

        if ($votes->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(PollVoteResource::collection($votes), 'data', 200);
        } else {
            throw new NotFound();
        }
    }
    /**
     * Read all information about all votes that match requested user_id.
     * 
     * @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function votesByUserId(Request $request)
    {
        $user_id = $request->user_id;
        //find votes belong to user_id
        $votes = PollVote::where('user_id', $user_id)->get();

        if ($votes->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(PollVoteResource::collection($votes), 'data', 200);
        } else {
            throw new NotFound();
        }
    }

    public function getPostVotesUsers($post_id, $user_id = null)
    {
        //get the user related to the votes and remove the duplicates
        $users = PollVote::where('post_id', $post_id)
            ->with('user')
            ->get()
            ->unique('user_id')
            ->map(function ($vote) {
                return $vote->user;
            });

        //get 10 users
        $limited = $users->take(10);

        //if the user id is sent, check if the user is in the list
        if ($user_id) {
            $user = User::find($user_id);
            if ($user && $limited->where('id', $user_id)->count() == 0) {
                $limited->prepend($user);
            } else if ($user && $limited->where('id', $user_id)->count() > 0) {
                //move the user to the top of the list
                $limited = $limited->filter(function ($value, $key) use ($user_id) {
                    return $value->id != $user_id;
                });
                $limited->prepend($user);
            }
        }

        return $this->jsonResponseWithoutMessage([
            'users' => UserInfoResource::collection($limited),
            'count' => $users->count(),
        ], 'data', 200);
    }
}