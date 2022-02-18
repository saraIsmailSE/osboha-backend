<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PollVote;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class PollVoteController extends Controller
{
    use ResponseJson;
   
    public function index()
    {
        $votes = PollVote::all();
        if($votes){
            return $this->jsonResponseWithoutMessage($votes, 'data',200);
        }
        else{
           // throw new NotFound;
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'post_id' => 'required',
            'option' => 'required',
        ]);
     
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }    
        if(Auth::user()->can('create vote')){
            PollVote::create($request->all());
            return $this->jsonResponseWithoutMessage("Vote Craeted Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;   
        }
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
            return $this->jsonResponseWithoutMessage($vote, 'data',200);
        }
        else{
           // throw new NotFound;
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'post_id' => 'required',
            'option' => 'required',
            'poll_vote_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if(Auth::user()->can('edit vote')){
            $vote = PollVote::find($request->poll_vote_id);
            $vote->update($request->all());
            return $this->jsonResponseWithoutMessage("Vote Updated Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;   
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

        if(Auth::user()->can('delete vote')){
            $vote = PollVote::find($request->poll_vote_id);
            $vote->delete();
            return $this->jsonResponseWithoutMessage("Vote Deleted Successfully", 'data', 200);
        }
        else{
            //throw new NotAuthorized;
        }
    }
}