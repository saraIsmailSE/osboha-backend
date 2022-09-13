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
    /**
     * Read all information about all votes in the system.
     * 
     * @return jsonResponseWithoutMessage
     */
    public function index()
    {
            $votes = PollVote::all();
            
            if($votes->isNotEmpty()){
                return $this->jsonResponseWithoutMessage(PollVoteResource::collection($votes), 'data',200);
            }
            else{
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
            'post_id' => 'required',
            'option' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }  

        $input = $request->all();
        $input['user_id'] = Auth::id();
        $option = $request->option;
        $input['option'] = serialize($option);
        PollVote::create($input);
        return $this->jsonResponseWithoutMessage("Vote Created Successfully", 'data', 200);
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
        if($vote){
            return $this->jsonResponseWithoutMessage(new PollVoteResource($vote), 'data',200);
        }
        else{
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

        $input=$request->all();
        $vote = PollVote::find($request->id);

        if($vote){
            if(Auth::id() == $vote->user_id){
                $option = $request->option;
                $input['option'] = serialize($option);
                $vote->update($input);
                //$vote->update($request->all());

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

        if($votes->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(PollVoteResource::collection($votes), 'data', 200);
        }else{
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

        if($votes->isNotEmpty()){
            return $this->jsonResponseWithoutMessage(PollVoteResource::collection($votes), 'data', 200);
        }else{
            throw new NotFound();
        }
    }
}