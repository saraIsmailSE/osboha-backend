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
use App\Traits\AmbassadorsTrait;
use App\Traits\PathTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AmbassadorsRequestsController extends Controller
{
    use ResponseJson, SignupTrait, PathTrait, AmbassadorsTrait;



    /*
        # Check Ambassador Endpoint Documentation

        This endpoint checks if a user is assigned as an ambassador and retrieves details about their ambassadorship.

        ## Endpoint

        `GET /check-ambassador/{user_id}`

        ## Parameters

        - `user_id` (integer, required): The unique identifier of the user to check for ambassadorship.

        ## Functionality

        - Retrieves the user with the specified ID from the database.
        - Checks if the user exists.
        - Retrieves the latest user group where the user is assigned as an ambassador.
        - Returns details about the user's ambassadorship if found.

        ## Response

        A successful response will return a JSON object containing the details of the user's ambassadorship if they are assigned as an ambassador.

        ## Errors

        - `500 Internal Server Error`: If an unexpected error occurs during processing.
    */
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
        - Checks if the user has appropriate roles (admin, advisor, consultant, supervisor, special care coordinator, special care supervisor) to create a request.
        - Checks if the group already has an unclosed request.
        - Checks if the group type allows requests (follow-up or special care).
        - Creates a new ambassadors request if all conditions are met.

        ## Response

        A successful response will return a JSON object containing the details of the newly created ambassadors request.

        ## Errors

        - `500 Internal Server Error`: If validation fails or if an unexpected error occurs during processing.
        - `406 Not Acceptable`: If the request already has an unclosed request or if the group type [Not a followup - special_care] does not allow requests.
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
        if (Auth::user()->hasanyrole('admin|advisor|consultant|supervispr|special_care_coordinator|special_care_supervisor')) {

            //check latest request
            $current_request = AmbassadorsRequests::where('group_id', $request->group_id)->where('is_done', 0)->latest()->first();
            if ($current_request) {
                //if there is a unclosed request
                return $this->jsonResponseWithoutMessage("already have request", 'data', 406);
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


    /*
        # Allocate Ambassador Endpoint Documentation

        This endpoint is used to allocate an ambassador to a group leader based on specified criteria.

        ## Endpoint

        `POST /allocate-ambassador/{leader_gender}/{ambassador_id?}`

        ## Parameters

        - `leader_gender` (string, required): The gender of the group leader.
        - `ambassador_id` (integer, optional): The ID of the ambassador to allocate. If not provided, the authenticated user's ID will be used.

        ## Functionality

        - Attempts to allocate an ambassador to a group leader based on the specified gender criteria.
        - If `ambassador_id` is not provided, the authenticated user is considered as the ambassador.
        - Selects a suitable group to allocate the ambassador based on the leader's gender and availability.
        - Creates a user group record for the ambassador and assigns them to the selected group.
        - Establishes a parent-child relationship between the ambassador and the group leader.
        - Notifies the group leader and the ambassador about the allocation.
        - Checks if the allocation request is complete and notifies the leader accordingly.

        ## Response

        A successful response will return a JSON object containing the details of the allocation process.

        ## Errors

        - `200 OK`: The allocation process is completed successfully, and the response contains the relevant details.
        - `200 OK` with empty response: If no suitable group or leader is found based on the specified criteria.

        ## Example

        To allocate the authenticated user (ambassador) to a group leader of the specified gender:
        ```
        POST /allocate-ambassador/male
        ```

        To allocate a specific ambassador to a group leader of the specified gender:
        ```
        POST /allocate-ambassador/female/123
        ```
    */

    public function allocateAmbassador($leader_gender, $ambassador_id = null)
    {

        try {
            DB::beginTransaction();

            if (is_null($ambassador_id)) {
                $ambassador = User::Find(Auth::id());
            } else {
                $ambassador = User::Find($ambassador_id);
            }

            $ambassador->leader_gender = $leader_gender;
            $ambassador->save();
            $ambassador->fresh();

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


    /*
        # List Ambassadors Requests Endpoint Documentation

        This endpoint retrieves a list of ambassadors requests based on specified criteria.

        ## Endpoint

        `GET /ambassadors-request/list/{retrieveType}/{is_done}/{name?}`

        ## Parameters

        - `retrieveType` (string, required): The type of groups to retrieve requests for. Possible values are "special_care" or "followup".
        - `is_done` (boolean, required): Flag to indicate whether to retrieve completed (true) or pending (false) requests.
        - `name` (string, optional): Filter requests by group name (default: '').

        ## Functionality

        - Retrieves ambassadors requests based on the specified criteria.
        - Supports filtering by group type (special care or followup) and whether the request is completed or pending.
        - Optionally filters requests by group name.
        - Paginates the results with a default limit of 30 requests per page.

        ## Response

        A successful response will return a JSON object containing the list of requests along with metadata.

        ## Errors

        - `200 OK`: If requests are found based on the specified criteria, they will be returned.
        - `200 OK` with empty response: If no requests are found based on the specified criteria.

        ## Example

        To retrieve pending requests for followup groups:
        ```
        GET /ambassadors-request/list/followup/false
        ```

        To retrieve completed requests for special care groups with the name containing "example":
        ```
        GET /ambassadors-request/list/special_care/true/example
        ```
    */
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

    /*
        # Update Ambassadors Request Endpoint Documentation

        This endpoint allows authorized users to update an existing ambassadors request.

        ## Endpoint

        `POST /ambassadors-request/update`

        ## Parameters

        In the request body, the following parameters are expected:

        - `members_num` (integer, required): The number of members for the request.
        - `ambassadors_gender` (string, required): The gender of the ambassadors for the request.
        - `leader_gender` (string, required): The gender of the leader for the request.
        - `request_id` (integer, required): The unique identifier of the ambassadors request to update.

        ## Functionality

        - Validates the input to ensure all required fields are present and correctly formatted.
        - Checks if the authenticated user has the necessary role to perform the update operation.
        - Retrieves the ambassadors request by its ID.
        - Updates the specified fields of the ambassadors request.
        - Saves the updated ambassadors request.

        ## Response

        A successful response will return a JSON object containing the updated ambassadors request details.

        ## Errors

        - `500 Internal Server Error`: If validation fails or an unexpected error occurs during processing.
        - `404 Not Found`: If the ambassadors request with the specified ID is not found.
    */

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'members_num' => 'required',
            'ambassadors_gender' => 'required',
            'leader_gender' => 'required',
            'request_id' => 'required|exists:ambassadors_requests,id',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $authID = Auth::id();
        if (Auth::user()->hasanyrole('admin|advisor|consultant|supervispr|special_care_coordinator|special_care_supervisor')) {

            $ambassadorsRequest = AmbassadorsRequests::find($request->request_id);
            if ($ambassadorsRequest) {

                $ambassadorsRequest->members_num = $request->members_num;
                $ambassadorsRequest->ambassadors_gender = $request->ambassadors_gender;
                $ambassadorsRequest->leader_gender = $request->leader_gender;
                $ambassadorsRequest->save();

                return $this->jsonResponseWithoutMessage($ambassadorsRequest, 'data', 200);
            } else {
                return $this->jsonResponseWithoutMessage("request not found", 'data', 404);
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /*
        # Delete Ambassadors Request Endpoint Documentation

        This endpoint is used to delete an ambassadors request.

        ## Endpoint

        `DELETE /ambassadors-requests/{id}`

        ## Parameters

        - `id` (integer, required): The ID of the ambassadors request to delete.

        ## Functionality

        - Deletes the ambassadors request with the specified ID from the database.

        ## Response

        A successful response will indicate that the request has been deleted.

        ## Errors

        - `200 OK`: The request is successfully deleted.
        - `403 Forbidden`: The user is not authorized to delete ambassadors requests.

        ## Example

        To delete an ambassadors request with ID 123:
        ```
        DELETE /ambassadors-requests/123
        ```
    */
    public function delete($id)
    {
        if (Auth::user()->hasanyrole('admin|advisor|consultant|supervispr|special_care_coordinator|special_care_supervisor')) {
            $ambassadorsRequest = AmbassadorsRequests::find($id);
            $ambassadorsRequest->delete();
            return $this->jsonResponseWithoutMessage("deleted", 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    public function statistics($timeFrame)
    {
        try {
            $statistics = $this->getStatistics($timeFrame);
            return $this->jsonResponseWithoutMessage($statistics, 'data', 200);
        } catch (\Exception $e) {
            Log::channel('newUser')->info($e);
            return $this->jsonResponseWithoutMessage("ERROR", 'data', 400);
        }
    }
}
