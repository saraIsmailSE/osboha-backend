<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AmbassadorsRequests;
use App\Models\Group;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserParent;
use App\Notifications\MailAmbassadorDistribution;
use App\Notifications\MailAmbassadorDistributionToYourTeam;
use App\Traits\SignupTrait;
use App\Traits\PathTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AmbassadorsRequestsController extends Controller
{
    use ResponseJson, SignupTrait, PathTrait;



    //check if new user is already an ambassador
    public function checkAmbassador($user_id)
    {
        $user_group = null;
        $user = User::find($user_id);
        if ($user) {
            //return last user group result as ambassador
            $user_group = UserGroup::with('group')->where('user_id', $user->id)->where('user_type', 'ambassador')->latest()->first();
        }
        return $this->jsonResponseWithoutMessage($user_group, 'data', 200);
    }


    /*
        # Create Ambassadors Request Endpoint Documentation

        This endpoint allows users with specific roles to create requests for joining a group as an ambassador.

        ## Endpoint

        `POST /ambassadors-request/create`

        ## Parameters

        In the request body, the following parameters are expected:

        - `members_num` (integer, required): The number of members requested to join the group.
        - `ambassadors_gender` (string, required): The preferred gender for the requested members.
        - `leader_gender` (string, required): The gender of the group leader.
        - `group_id` (integer, required): The ID of the group to which the user wants to request membership.

        ## Functionality

        - Validates the input data to ensure all required fields are present and correctly formatted.
        - Checks if the user has appropriate roles (admin, advisor, consultant, supervisor, special care coordinator) to create a request.
        - Checks if the group already has an unclosed request.
        - Checks if the group type allows requests (follow-up or special care).
        - Creates a new ambassadors request if all conditions are met.

        ## Response

        A successful response will return a JSON object containing the details of the newly created ambassadors request.

        ## Errors

        - `500 Internal Server Error`: If validation fails or if an unexpected error occurs during processing.
        - `406 Not Acceptable`: If the group already has an unclosed request or if the group type [Not a followup - special_care] does not allow requests.
        - `403 Forbidden`: If the user does not have the necessary roles to create a request.
    */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'members_num' => 'required',
            'ambassadors_gender' => 'required',
            'leader_gender' => 'required',
            'group_id' => 'required|exists:groups,id',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $authID = Auth::id();
        if (Auth::user()->hasanyrole('admin|advisor|consultant|supervispr|special_care_coordinator')) {

            //check latest request
            $current_request = AmbassadorsRequests::where('group_id', $request->group_id)->where('is_done', 0)->latest()->first();
            if ($current_request) {
                //if there is a unclosed request
                return $this->jsonResponseWithoutMessage("This group already have request", 'data', 406);
            } else {

                $group = Group::with('type')->where('id', $request->group_id)->first();
                //check group_type
                if ($group->type->type == 'followup' || $group->type->type == 'special_care') {

                    $ambassadorsRequest = AmbassadorsRequests::create([
                        'members_num' => $request->members_num,
                        'ambassadors_gender' => $request->ambassadors_gender,
                        'leader_gender' => $request->leader_gender,
                        'applicant_id' => $authID,
                        'group_id' => $request->group_id,

                    ]);
                    return $this->jsonResponseWithoutMessage($ambassadorsRequest, 'data', 200);
                } else {
                    return $this->jsonResponseWithoutMessage("You can not request member for this group", 'data', 406);
                }
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /*
        # Show Ambassadors Request Endpoint Documentation

        This endpoint retrieves details about a specific ambassadors request.

        ## Endpoint

        `GET /ambassadors-request/{id}`

        ## Parameters

        - `id` (integer, required): The unique identifier of the ambassadors request.

        ## Functionality

        - Retrieves the ambassadors request with the specified ID from the database.

        ## Response

        A successful response will return a JSON object containing the details of the ambassadors request identified by the provided ID.

        ## Errors

        - `500 Internal Server Error`: If an unexpected error occurs during processing.
    */
    public function show($id)
    {
        $ambassadorsRequest = AmbassadorsRequests::with('group')->with('applicant')->with('ambassadors')->find($id);
        return $this->jsonResponseWithoutMessage($ambassadorsRequest, 'data', 200);
    }

    /*
        # Latest Ambassadors Request Endpoint Documentation

        This endpoint retrieves the latest ambassadors request for a specific group.

        ## Endpoint

        `GET /ambassadors-request/latest/{group_id}`

        ## Parameters

        - `group_id` (integer, required): The unique identifier of the group for which to retrieve the latest ambassadors request.

        ## Functionality

        - Retrieves the latest ambassadors request associated with the specified group ID from the database.

        ## Response

        A successful response will return a JSON object containing the details of the latest ambassadors request for the provided group ID.

        ## Errors

        - `500 Internal Server Error`: If an unexpected error occurs during processing.
    */
    public function latest($group_id)
    {
        $latestRequest = AmbassadorsRequests::where('group_id', $group_id)->with('ambassadors')->latest()->first();
        return $this->jsonResponseWithoutMessage($latestRequest, 'data', 200);
    }


    public function allocateAmbassador($leader_gender, $ambassador_id = null)
    {

        try {
            DB::beginTransaction();

            if (is_null($ambassador_id)) {
                $ambassador = User::Find(Auth::id());
            } else {
                $ambassador = User::Find($ambassador_id);
            }


            $response['message'] = 'no group found';
            $teamRequest = $this->selectTeam($ambassador, $leader_gender);
            if ($teamRequest) {
                //get group leader
                $leader = UserGroup::where('group_id', $teamRequest->group->id)->whereIn('user_type', ['leader', 'special_care_leader'])->whereNull('termination_reason')->first();

                if ($leader) {

                    $response['leader'] = $leader;
                    $response['group'] = $teamRequest;

                    //create user group record
                    UserGroup::create(
                        [
                            'user_type' => 'ambassador',
                            'group_id' => $teamRequest->group->id,
                            'user_id' => $ambassador->id

                        ]
                    );

                    //crete user parent relation
                    $ambassador->parent_id = $leader->user->id;
                    $ambassador->request_id = $teamRequest->id;
                    $ambassador->is_excluded = 0;
                    $ambassador->is_hold = 0;

                    $ambassador->save();

                    UserParent::create([
                        'user_id' => $ambassador->id,
                        'parent_id' =>  $leader->user->id,
                        'is_active' => 1,
                    ]);

                    //check if request Done
                    $is_done = $this->checkIsDone($teamRequest->id);
                    if ($is_done) {
                        $leaderToNotify = User::find($leader->user->id);
                        $leaderToNotify->notify(new MailAmbassadorDistributionToYourTeam($teamRequest->group->id));
                    }
                    //inform leader and user
                    $ambassador->notify(new MailAmbassadorDistribution($teamRequest->group->id));

                    $msg = "تم توزيع سفير للمجموعة:  " . $teamRequest->group->name;
                    (new NotificationController)->sendNotification($leader->user->id, $msg, ROLES, $this->getGroupPath($teamRequest->group->id));

                    DB::commit();

                    $response['message'] = 'done successfully';
                    return $this->jsonResponseWithoutMessage($response, 'data', 200);
                } else {
                    $response['message'] = 'group without leader';
                    return $this->jsonResponseWithoutMessage($response, 'data', 200);
                }
            }

            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::channel('newUser')->info($e);
        }
    }

    public function listRequests($retrieveType, $is_done, $name = '')
    {
        switch ($retrieveType) {
            case 'special_care':
                $groupType = ['special_care'];
                break;
            case 'followup':
                $groupType = ['followup'];
                break;
            default:
                $groupType = [
                    'followup',
                    'special_care',
                ];
        }
        $requests = AmbassadorsRequests::whereHas('group.type', function ($q) use ($groupType) {
            $q->whereIn('type', $groupType);
        })
            ->whereHas('group', function ($q) use ($name) {
                $q->where('name', 'like', '%' . $name . '%');
            })
            ->where('is_done', $is_done)
            ->with('applicant', 'group')->withCount('ambassadors')
            ->paginate(30);


        if ($requests->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'requests' => $requests,
                'total' => $requests->total(),
                'last_page' => $requests->lastPage(),
            ], 'data', 200);
        }
        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }
}
