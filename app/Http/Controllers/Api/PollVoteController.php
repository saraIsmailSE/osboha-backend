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
        if(Auth::user()->can('list poll-votes')){
            $votes = PollVote::all();
            if($votes){
                return $this->jsonResponseWithoutMessage(PollVoteResource::collection($votes), 'data',200);
            }
            else{
                throw new NotFound;
            }
        }
        else{
            throw new NotAuthorized;
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

            $option = $request->option;
            if (is_array($option)){
                serialize($option);
            }

            PollVote::create($request->all());
            PollVote::create(new PollVoteResource($request->all()));

            return $this->jsonResponseWithoutMessage("Vote Craeted Successfully", 'data', 200);
        }
        else{
            throw new NotAuthorized;   
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
            return $this->jsonResponseWithoutMessage(new PollVoteResource($vote), 'data',200);
        }
        else{
           throw new NotFound;
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
            if ($vote){
                $vote->update($request->all());
                return $this->jsonResponseWithoutMessage("Vote Updated Successfully", 'data', 200);
            }
            else{
                throw new NotFound;   
            }
        }
        else{
            throw new NotAuthorized;   
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
            if($vote){
                $vote->delete();
                return $this->jsonResponseWithoutMessage("Vote Deleted Successfully", 'data', 200);
            }
            else{
                throw new NotFound;
            }
        }
        else{
            //throw new NotAuthorized;
        }
    }
}