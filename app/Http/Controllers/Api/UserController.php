<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserInfoResource;
use App\Models\ExceptionType;
use App\Models\Group;
use App\Models\GroupType;
use App\Models\User;
use App\Models\UserGroup;
use App\Models\UserParent;
use App\Models\Week;
use App\Traits\MarkTrait;
use App\Models\SocialMedia;
use App\Models\Thesis;
use App\Models\UserException;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;


use App\Traits\UserParentTrait;
use Carbon\Carbon;

class UserController extends Controller
{
    use ResponseJson, UserParentTrait, MarkTrait;

    /**
     * Show user`s basic info.
     *
     * @param  $user_id
     * @return App\Http\Resources\UserInfoResource ;
     */
    public function show($user_id)
    {
        $user = User::without('userProfile')->find($user_id);
        return $this->jsonResponseWithoutMessage(new UserInfoResource($user), 'data', 200);
    }

    public function searchUsers(Request $request)
    {
        $searchQuery = $request->query('search');
        $users = User::where('name', 'LIKE', '%' . $searchQuery . '%')
            ->whereNotNull('parent_id')
            ->get();
        return $this->jsonResponseWithoutMessage(UserInfoResource::collection($users), "data", 200);
    }
    public function searchByEmail($email)
    {
        $response['user'] = User::with('parent')->where('email', $email)->first();
        if ($response['user']) {
            $response['roles'] = $response['user']->getRoleNames();
            $response['in_charge_of'] = User::where('parent_id', $response['user']->id)->get();
            $response['followup_team'] = UserGroup::with('group')->where('user_id', $response['user']->id)->where('user_type', 'ambassador')->whereNull('termination_reason')->first();
            $response['groups'] = UserGroup::with('group')->where('user_id', $response['user']->id)->get();
            //Data for the last four weeks
            $weekIds = Week::latest()->take(4)->pluck('id');
            $response['ambassadorMarks'] = $this->ambassadorWeekMark($response['user']->id, $weekIds);

            if (!Auth::user()->hasAnyRole(['admin'])) {
                $logInfo = ' قام ' . Auth::user()->name . " بالبحث عن سفير ";
                Log::channel('user_search')->info($logInfo);
            }
            return $this->jsonResponseWithoutMessage($response, "data", 200);
        } else {
            return $this->jsonResponseWithoutMessage(null, "data", 200);
        }
    }

    public function inChargeOfSearch($email)
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            $in_charge_of = UserParent::with('child')->where('parent_id', $user->id)->orderBy('is_active', 'desc')
                ->paginate(25);
            return $this->jsonResponseWithoutMessage([
                'user' => $user,
                'in_charge_of' => $in_charge_of,
                'total' => $in_charge_of->total(),
                'last_page' => $in_charge_of->lastPage(),
            ], 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage(null, "data", 200);
        }
    }
    public function searchByName($name)
    {
        $response['users']  = User::with(['parent', 'groups' => function ($query) {
            $query->wherePivot('user_type', 'ambassador')
                ->whereNull('termination_reason')->get();
        }])
            ->where('name', 'LIKE', "%{$name}%")
            ->withCount('children')->get();
        if ($response['users']) {
            return $this->jsonResponseWithoutMessage($response, "data", 200);
        } else {
            return $this->jsonResponseWithoutMessage(null, "data", 200);
        }
    }

    public function listInChargeOf()
    {
        $response = User::where('parent_id', Auth::id())->get();
        return $this->jsonResponseWithoutMessage($response, "data", 200);
    }

    public function assignToParent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user' => 'required|email',
            'head_user' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        try {
            //check user exists
            $user = User::where('email', $request->user)->first();
            if ($user) {
                //check head_user exists
                $head_user = User::where('email', $request->head_user)->first();
                if ($head_user) {

                    if ($head_user->id == $user->id) {
                        return $this->jsonResponseWithoutMessage("لا يمكن أن يكون الشخص مسؤولاً عن نفسه", 'data', 200);
                    }

                    $user_last_role = $user->roles->first();
                    $head_user_last_role = $head_user->roles->first();

                    //check if head user role is greater that user role
                    if ($head_user_last_role->id < $user_last_role->id) {
                        //if last role less than the new role => assign ew role
                        // Link with head user
                        $user->parent_id = $head_user->id;
                        $user->save();
                        UserParent::where("user_id", $user->id)->update(["is_active" => 0]);
                        UserParent::create([
                            'user_id' => $user->id,
                            'parent_id' =>  $head_user->id,
                            'is_active' => 1,
                        ]);

                        $msg = "قام " . Auth::user()->name . " بـ تعيين : " . $head_user->name . " مسؤولًا عنك";
                        (new NotificationController)->sendNotification($user->id, $msg, ROLES);

                        $msg = "قام " . Auth::user()->name . " بـ تعيينك مسؤولاً عن : " . $user->name;
                        (new NotificationController)->sendNotification($head_user->id, $msg, ROLES);

                        $logInfo = ' قام ' . Auth::user()->name . " بـ تعيين  "  . $head_user->name . " مسؤولاً عن " .  $user->name;
                        Log::channel('community_edits')->info($logInfo);

                        return $this->jsonResponseWithoutMessage("تم التعيين", 'data', 200);
                    } else {
                        return $this->jsonResponseWithoutMessage("يجب أن تكون رتبة المسؤول أعلى من رتبة المسؤول عنه", 'data', 200);
                    }
                } else {
                    return $this->jsonResponseWithoutMessage("المسؤول غير موجود", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("المستخدم غير موجود", 'data', 200);
            }
        } catch (\Illuminate\Database\QueryException $error) {
            return $this->jsonResponseWithoutMessage($error, 'data', 500);
        }
    }

    public function getInfo($id)
    {
        try {
            //get user info
            $user = User::findOrFail($id);
            return $this->jsonResponseWithoutMessage($user, 'data', 200);
        } catch (\Illuminate\Database\QueryException $error) {
            return $this->jsonResponseWithoutMessage($error, 'data', 500);
        }
    }
    public function listUnAllowedToEligible()
    {
        try {
            $users = User::where('allowed_to_eligible', 0)->get();
            return $this->jsonResponseWithoutMessage($users, 'data', 200);
        } catch (\Error $e) {
            return $this->jsonResponseWithoutMessage('Error Happend', $e, 200);
        }
    }

    public function acceptEligibleUser($id)
    {
        $user = User::find($id);
        try {
            $user->update(['allowed_to_eligible' => 1]);
            $user->save();
            $this->deleteOfficialDoc($user->id);

            $msg = "تمت الموافقة، يمكنك توثيق الكتب بنجاح";
            (new NotificationController)->sendNotification($user->id, $msg, ROLES,);

            return $this->jsonResponseWithoutMessage($user->refresh(), 'data', 200);
        } catch (\Error $e) {
            return $this->jsonResponseWithoutMessage('User does not exist', $e, 200);
        }
    }

    public function deActiveUser(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            "id" => "required|int",
            "rejectNote" => "required|string",

        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $user = User::where('id', $request->id)->update(['allowed_to_eligible' => 2]);
        $userToNotify = User::find($request->id);
        $userToNotify->notify(new \App\Notifications\RejectUserEmail($request->rejectNote));
        $this->deleteOfficialDoc($request->id);

        $result = $user;

        if ($result == 0) {
            return $this->jsonResponseWithoutMessage('User does not exist', 'data', 404);
        }
        return $this->jsonResponseWithoutMessage($result, 'data', 200);
    }

    public function deleteOfficialDoc($userID)
    {
        $pathToRemove = '/assets/images/Official_Document/' . 'osboha_official_document_' . $userID;

        //get all files with same name no matter what extension is
        $filesToRemove = glob(public_path($pathToRemove . '.*'));

        foreach ($filesToRemove as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }


    function retrieveNestedUsers($parentId)
    {
        $response['root_user'] = User::find($parentId);
        $response['nested_users'] = $this->nestedUsers($parentId);

        $roles = ['admin', 'consultant', 'advisor', 'supervisor', 'leader'];
        $response['followup_groups'] = UserGroup::where('user_id',  $parentId)->whereIn('user_type', $roles)->whereNull('termination_reason')
            ->whereHas('group.type', function ($q) {
                $q->where('type', '=', 'followup');
            })->count();
        $response['supervising_groups'] = UserGroup::where('user_id',  $parentId)->whereIN('user_type', $roles)->whereNull('termination_reason')
            ->whereHas('group.type', function ($q) {
                $q->where('type', '=', 'supervising');
            })->count();


        //FOR ADMIN
        $response['total_followup_groups'] = Group::where('is_active', 1)
            ->whereHas('type', function ($q) {
                $q->where('type', '=', 'followup');
            })->count();
        $response['total_supervising_groups'] = Group::where('is_active', 1)
            ->whereHas('type', function ($q) {
                $q->where('type', '=', 'supervising');
            })->count();

        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    function getUsersOnHoldByMonthAndGender($contact_status, $month, $gender)
    {
        $year = Carbon::now()->year;
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $query = User::without('userProfile')
            ->with(['groups' => function ($query) {
                $query->wherePivot('user_type', 'ambassador')
                    ->wherePivot('termination_reason', 'withdrawn');
            }, 'withdrawnExceptions', 'socialMedia', 'contactsAsAWithdrawn'])
            ->where('is_hold', 1)
            ->where(function ($query) use ($contact_status) {
                if ($contact_status == 0) {
                    // If contact_status is 0, we want to include users with null contactsAsAWithdrawn
                    $query->whereDoesntHave('contactsAsAWithdrawn')
                        ->orWhereHas('contactsAsAWithdrawn', function ($query) use ($contact_status) {
                            $query->where('contact', $contact_status);
                        });
                } else {
                    // Otherwise, only include users where contactsAsAWithdrawn.contact matches contact_status
                    $query->whereHas('contactsAsAWithdrawn', function ($query) use ($contact_status) {
                        $query->where('contact', $contact_status);
                    });
                }
            })
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->orderBy('updated_at');

        if ($gender !== 'both') {
            $query->where('gender', $gender);
        }
        $users = $query->paginate(30);

        // Keep pagination details
        $paginationDetails = [
            'total' => $users->total(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'current_page' => $users->currentPage(),
        ];

        // Filter the users
        $filteredUsers = $users->map(function ($user) {
            $latestGroup = $user->groups->first();
            $latestException = $user->withdrawnExceptions->first();
            $user->setRelation('groups', collect([$latestGroup]));
            $user->setRelation('latestException', collect([$latestException]));
            return $user;
        })->filter(function ($user) {
            return $user->groups->isNotEmpty() && $user->withdrawnExceptions->isNotEmpty();
        });

        $statistics['total_holds'] = DB::table('users')
            ->where('is_hold', 1)
            ->count();
        $statistics['contact_done'] = DB::table('contacts_with_withdrawns')
            ->where('contact', 1)
            ->count();
        // $statistics['contact_not_done'] = DB::table('contacts_with_withdrawns')
        //     ->where('contact', 0)
        //     ->count();
        $statistics['consented_to_return'] = DB::table('contacts_with_withdrawns')
            ->where('return', 1)
            ->count();
        $statistics['refused_to_return'] = DB::table('contacts_with_withdrawns')
            ->where('return', 0)
            ->count();
        $statistics['did_not_respond'] = DB::table('contacts_with_withdrawns')
            ->where('return', -1)
            ->count();



        if ($filteredUsers->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'ambassadors' => $filteredUsers,
                'statistics' => $statistics,
                'total' => $paginationDetails['total'],
                'last_page' => $paginationDetails['last_page'],
                'per_page' => $paginationDetails['per_page'],
                'current_page' => $paginationDetails['current_page'],
            ], 'data', 200);
        }

        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }

    function withdrawnAmbassadorDetails($user_id)
    {

        $response['user'] = User::with('socialMedia')->find($user_id);
        if ($response['user']) {
            $response['group'] = UserGroup::with('group')->where('user_id', $user_id)->where('user_type', 'ambassador')->where('termination_reason', 'withdrawn')->first();
            $withdrawn = ExceptionType::where('type', config("constants.WITHDRAWN_TYPE"))->first();
            $response['exception'] = UserException::where('user_id', $user_id)->where('type_id', $withdrawn->id)->where('status', 'accepted')->first();
            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        }

        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }

    public function updateInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|string",
            "last_name" => "required|string",
            'facebook' => 'required_without_all:whatsapp,instagram,telegram',
            'whatsapp' => 'required_without_all:facebook,instagram,telegram',
            'instagram' => 'required_without_all:facebook,whatsapp,telegram',
            'telegram' => 'required_without_all:facebook,whatsapp,instagram',

        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $authUser = User::find(Auth::id());
        $authUser->name = $request->name;
        $authUser->last_name = $request->last_name;
        $authUser->save();

        //update social media
        $socialAccounts = SocialMedia::updateOrCreate(
            ['user_id' => Auth::id()],
            [
                'facebook' => $request->get('facebook'),
                'whatsapp' => $request->get('whatsapp'),
                'instagram' => $request->get('instagram'),
                'telegram' => $request->get('telegram')
            ]
        );
        return $this->jsonResponseWithoutMessage("updated", 'data', 200);
    }
    public function updateUserName(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|string",
            "last_name" => "required|string",
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $authUser = User::without('userProfile')->find(Auth::id());
        $authUser->name = $request->name;
        $authUser->last_name = $request->last_name;
        $authUser->save();

        return $this->jsonResponseWithoutMessage("updated", 'data', 200);
    }
}
