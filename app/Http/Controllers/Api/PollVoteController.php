<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PollVote;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\PollVoteResource;

class PollVoteController extends Controller
{
    use ResponseJson;
   
    public function index()
    {
            //$votes = PollVote::all();
            $votes = PollVote::where('user_id', Auth::id())->get();
            if($votes->isNotEmpty()){
                return $this->jsonResponseWithoutMessage(PollVoteResource::collection($votes), 'data',200);
            }
            else{
                throw new NotFound;
            }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required',
            'option' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        $input = $request->all();
        $input['user_id'] = Auth::id();
        $input['option'] = serialize($request->option);

        PollVote::create($input);
        return $this->jsonResponseWithoutMessage("Vote Created Successfully", 'data', 200);
    }

    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'poll_vote_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    

        $vote = PollVote::find($request->poll_vote_id);
        if($vote){
            return $this->jsonResponseWithoutMessage(new PollVoteResource($vote), 'data',200);
        }
        else{
           throw new NotFound;
        }
    }

    public function votesByPostId(Request $request)
    {
        $post_id = $request->post_id;

        //find votes belong to post_id
        $votes = PollVote::where('post_id', $post_id)->get();

        if($votes->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(PollVoteResource::collection($votes), 'data', 200);
        }else{
            throw new NotFound();
        }
    }

    public function votesByAuthUser()
    {
        //find votes belong to Auth user
        $votes = PollVote::where('user_id', Auth::id())->get();

        if($votes){
            return $this->jsonResponseWithoutMessage(PollVoteResource::collection($votes), 'data', 200);
        }else{
            throw new NotFound();
        }
    }
    
    public function votesByUserId(Request $request)
    {
        $user_id = $request->user_id;
        //find votes belong to user_id
        $votes = PollVote::where('user_id', $user_id)->get();

        if($votes){
            return $this->jsonResponseWithoutMessage(PollVoteResource::collection($votes), 'data', 200);
        }else{
            throw new NotFound();
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'post_id' => 'required',
            'option' => 'required',
            'poll_votes_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $vote = PollVote::find($request->poll_votes_id);
        if($vote){
            if(Auth::id() == $vote->user_id){
                $input = $request->all();
                $input['option'] = serialize($request->option);
                $vote->update($input);
                return $this->jsonResponseWithoutMessage("Vote Updated Successfully", 'data', 200);
            }
            else{
                throw new NotAuthorized;   
            }
        }
        else{
            throw new NotFound; 
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'poll_vote_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        $vote = PollVote::find($request->poll_vote_id);
        if($vote){
            if(Auth::id() == $vote->user_id){
                $vote->delete();
                return $this->jsonResponseWithoutMessage("Vote Deleted Successfully", 'data', 200);
            }
            else{
                throw new NotAuthorized;
            }
        }
        else{
            throw new NotFound;
        }
    }
}