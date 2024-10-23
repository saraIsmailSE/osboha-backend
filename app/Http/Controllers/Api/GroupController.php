<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GroupResource;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use App\Models\Group;
use App\Models\Media;
use App\Models\Timeline;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\BookResource;
use App\Models\LeaderRequest;
use App\Models\Mark;
use App\Models\User;
use App\Models\UserBook;
use App\Models\UserException;
use App\Models\Week;
use Illuminate\Support\Facades\DB;
use App\Models\Book;
use App\Models\ExceptionType;
use App\Models\TimelineType;
use App\Models\UserParent;
use App\Traits\GroupTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Description: GroupController for Osboha group.
 *
 * Methods:
 * - CRUD
 * - group posts list
 */

//all the queries of the group need enhancement because of the model relations (conditions with pivot table) - asmaa
class GroupController extends Controller
{

    use ResponseJson, MediaTraits, GroupTrait;

    /**
     * Get all groups.
     *
     * @return groups;
     */

    public function listGroups($retrieveType, $name = '')
    {
        switch ($retrieveType) {
            case 'special_care':
                $groupType = ['special_care'];
                break;
            case 'marathon':
                $groupType = ['marathon'];
                break;
            case 'advising':
                $groupType = ['advising'];
                break;
            case 'supervising':
                $groupType = ['supervising'];
                break;
            case 'followup':
                $groupType = ['followup'];
                break;
            default:
                $groupType = [
                    'followup',
                    'special_care',
                    'supervising',
                    'advising',
                    'consultation',
                    'Administration',
                    'marathon'
                ];
        }
        $groups = Group::whereHas('type', function ($q) use ($groupType) {
            $q->whereIn('type', $groupType);
        })->with('groupAdministrators')->withCount('users')
            ->where('name', 'like', '%' . $name . '%')
            ->paginate(30);


        if ($groups->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'groups' => $groups,
                'total' => $groups->total(),
                'last_page' => $groups->lastPage(),
            ], 'data', 200);
        }
        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }

    public function GroupByType($type)
    {

        $groups = Group::whereHas('type', function ($q) use ($type) {
            $q->where('type', '=', $type);
        })->orderBy('created_at', 'asc')
            ->get();
        if ($groups->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage($groups, 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage(null, 'data', 200);
        }
    }

    public function create(Request $request)
    {
        $input = $request->all();

        $validator = Validator::make($input, [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'type_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 400);
        }


        if (Auth::user()->can('create group')) {

            $timeLine_type = TimelineType::where('type', 'group')->first();
            $timeline = new Timeline;

            $timeline->type_id = $timeLine_type->id;
            $timeline->save();

            $input['creator_id'] = Auth::id();
            $input['timeline_id'] = $timeline->id;

            $group = Group::create($input);

            //add group creator to the group
            $userGroup = UserGroup::create([
                'user_id' => Auth::id(),
                'group_id' => $group->id,
                'user_type' => auth()->user()->roles()->pluck('name')->first()

            ]);
            $userGroup->save();

            $current_week_id = Week::latest()->pluck('id')->first();
            Mark::updateOrCreate(
                [
                    'user_id' => Auth::id(),
                    'week_id' => $current_week_id
                ],
                [
                    'user_id' => Auth::id(),
                    'week_id' => $current_week_id
                ],
            );

            /* BUG Parent of Admin is Another User

            $child = User::find(Auth::id());
            $parent = $child->parent;

            while ($parent !== null) {

                $parentRole = $parent->roles()->orderBy('id', 'asc')->first();

                $userGroup = UserGroup::create([
                    'user_id' => $parent->id,
                    'group_id' => $group->id,
                    'user_type', $parentRole
                ]);
                $userGroup->save();
                $child = $parent;
                $parent = $child->parentRole;
            }
            */
            return $this->jsonResponseWithoutMessage($group, 'data', 201);
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * Find an existing group by its id and display it.
     *
     * @param  $group_id
     * @return group info [users , administrators] - posts;
     */

    public function show($group_id)
    {

        $response['info'] = Group::with('users', 'groupAdministrators', 'leaderAndAmbassadors', 'groupSupportLeader')->withCount('userAmbassador')->where('id', $group_id)->first();
        if ($response['info']) {
            $response['authInGroup'] = UserGroup::where('user_id', Auth::id())->whereNull('termination_reason')->where('group_id', $group_id)
                ->latest() //admaa
                ->first();

            if ($response['authInGroup'] || Auth::user()->hasRole('admin')) {

                //group posts
                $response['post'] = Timeline::find($response['info']->id);

                //previous_week
                $response['previous_week'] = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();

                //week avg
                $response['week'] = Week::latest('id')->first();

                $response['week_avg'] = $this->groupAvg($group_id,  $response['week']->id, $response['info']->leaderAndAmbassadors->pluck('id'));

                $response['has_support_leader'] = $response['info']->groupSupportLeader->isNotEmpty();

                return $this->jsonResponseWithoutMessage($response, 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } //end if group found

        //group not found
        else {
            throw new NotFound;
        }
    }



    /**
     * Find an existing group by its id and display its basic information.
     *
     * @param  $group_id
     * @return group info;
     */

    public function showBasicInfo($group_id)
    {

        $group = Group::find($group_id);
        if ($group) {
            return $this->jsonResponseWithoutMessage($group, 'data', 200);
        } //end if group found
        else {
            throw new NotFound;
        }
    }

    /**
     * update group info.
     *
     * @param  Request $request
     * @return group;
     */


    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'type_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        try {

            $group = Group::where('id', $request->group_id)
                ->update(['name' => $request->name, 'description' => $request->description, 'type_id' => $request->type_id]);

            return $this->jsonResponseWithoutMessage($group, 'data', 200);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    public function delete($group_id)
    {
        if (Auth::user()->hasRole('admin')) {
            $group = Group::find($group_id);
            if ($group) {

                $group->delete();

                return $this->jsonResponseWithoutMessage('Group Deleted', 'data', 200);
            } else {
                throw new NotFound();
            }
        }
        //endif Auth

        else {
            throw new NotAuthorized;
        }
    }


    /**
     * Get all books belongs to group users.
     *
     * @param  $group_id
     * @return jsonResponseWithoutMessage;
     */
    public function books($group_id)
    {
        /*wrong query*/
        /*editted by @asmaa*/

        // $group = Group::with('users')->where('id', $group_id)->first();
        // $books = UserBook::whereIn('user_id', $group->pluck('id'))->get();

        $users = UserGroup::where('group_id', $group_id)->groupBy('user_id')->pluck('user_id');
        $books = UserBook::whereIn('user_id', $users)
            ->select('book_id')
            ->groupBy('book_id')->get()->pluck('book');
        if ($books) {
            return $this->jsonResponseWithoutMessage(
                BookResource::collection($books),
                'data',
                200
            );
        } else {
            throw new NotFound;
        }
    }


    /**
     * List all exceptions in a group by group Administrators
     *
     * @param $group_id
     * @return jsonResponseWithoutMessage
     */

    public function groupExceptions($group_id)
    {

        $withdrawn = Cache::remember('withdrawn', now()->addMinutes(120), function () {
            return ExceptionType::where('type', config("constants.WITHDRAWN_TYPE"))->first();
        });
        $userInGroup = UserGroup::where('group_id', $group_id)
            ->where('user_id', Auth::id())
            ->where('user_type', '!=', 'ambassador')
            ->pluck('user_type')
            ->first();

        //if no records, then the user is only an ambassador
        if ($userInGroup || Auth::user()->hasRole(['admin'])) {
            $response['week'] = Week::latest()->first();
            $response['group'] = Group::with('userAmbassador')->without(['Timeline','type'])
            ->where('id', $group_id)->first();
            $response['exceptions'] = UserException::without(['reviewer'])->whereIn('user_id', $response['group']->userAmbassador->pluck('id'))->where('type_id', '!=', $withdrawn->id)->latest()->get()->makeHidden(['current_assignee']);
            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * Filter group exceptions.
     *
     * @param  exception filter , group _id
     * @return jsonResponseWithoutMessage
     */
    public function exceptionsFilter($filter, $group_id)
    {

        $group = Group::with('userAmbassador')->without(['Timeline','type'])->where('id', $group_id)->first();
        $withdrawn = Cache::remember('withdrawn', now()->addMinutes(120), function () {
            return ExceptionType::where('type', config("constants.WITHDRAWN_TYPE"))->first();
        });

        if ($filter == 'oldest') {
            $exceptions = UserException::without(['reviewer'])->whereIn('user_id', $group->users->pluck('id'))->where('type_id', '!=', $withdrawn->id)->get()->makeHidden(['current_assignee']);
        } else if ($filter == 'latest') {
            $exceptions = UserException::without(['reviewer'])->whereIn('user_id', $group->users->pluck('id'))->where('type_id', '!=', $withdrawn->id)->latest()->get()->makeHidden(['current_assignee']);
        } else if ($filter == 'freez') {
            $exceptions = UserException::without(['reviewer'])->whereHas('type', function ($query) {
                $query->where('type', 'تجميد الأسبوع الحالي')
                    ->orWhere('type', 'تجميد الأسبوع القادم');
            })->whereIn('user_id', $group->users->pluck('id'))->latest()->get()->makeHidden(['current_assignee']);
        } else if ($filter == 'exceptional_freez') {
            $exceptions = UserException::without(['reviewer'])->whereHas('type', function ($query) {
                $query->where('type', 'تجميد استثنائي');
            })->whereIn('user_id', $group->users->pluck('id'))->latest()->get()->makeHidden(['current_assignee']);
        } else if ($filter == 'exams') {
            $exceptions = UserException::without(['reviewer'])->whereHas('type', function ($query) {
                $query->where('type', 'نظام امتحانات - شهري')
                    ->orWhere('type', 'نظام امتحانات - فصلي');
            })->whereIn('user_id', $group->users->pluck('id'))->latest()->get()->makeHidden(['current_assignee']);
        } else if ($filter == 'accepted') {
            $exceptions = UserException::without(['reviewer'])->where('status', 'accepted')->whereIn('user_id', $group->users->pluck('id'))->where('type_id', '!=', $withdrawn->id)->latest()->get()->makeHidden(['current_assignee']);
        } else if ($filter == 'pending') {
            $exceptions = UserException::without(['reviewer'])->where('status', 'pending')->whereIn('user_id', $group->users->pluck('id'))->where('type_id', '!=', $withdrawn->id)->latest()->get()->makeHidden(['current_assignee']);
        } else if ($filter == 'rejected') {
            $exceptions = UserException::without(['reviewer'])->where('status', 'rejected')->whereIn('user_id', $group->users->pluck('id'))->where('type_id', '!=', $withdrawn->id)->latest()->get()->makeHidden(['current_assignee']);
        } else if ($filter == 'finished') {
            $exceptions = UserException::without(['reviewer'])->where('status', 'finished')->whereIn('user_id', $group->users->pluck('id'))->where('type_id', '!=', $withdrawn->id)->latest()->get()->makeHidden(['current_assignee']);
        }

        return $this->jsonResponseWithoutMessage($exceptions, 'data', 200);
    }

    /**
     * Basic group marks.
     *
     * @param  group _id
     * @return group info , week satistics [100 - 0 -incomplete - most read]
     */
    public function BasicMarksView($group_id, $week_id)
    {
        $marks['group'] = Group::with('leaderAndAmbassadors')->where('id', $group_id)->first();
        $marks['group_users'] =  $marks['group']->leaderAndAmbassadors->count();

        $marks['freezed'] = Mark::where('week_id', $week_id)
            ->whereIn('user_id',  $marks['group']->leaderAndAmbassadors->pluck('id'))
            ->where('is_freezed', 1)
            ->count();
        $marks['full'] = Mark::where('week_id', $week_id)
            ->whereIn('user_id',  $marks['group']->leaderAndAmbassadors->pluck('id'))
            ->select(DB::raw('(reading_mark + writing_mark + support) as out_of_100'))
            ->having('out_of_100', 100)
            ->count();

        $marks['incomplete'] = Mark::where('week_id', $week_id)
            ->whereIn('user_id',  $marks['group']->leaderAndAmbassadors->pluck('id'))
            ->select(DB::raw('(reading_mark + writing_mark + support) as out_of_100'))
            ->having('out_of_100', '<', 100)
            ->having('out_of_100', '>', 0)
            ->count();

        $marks['zero'] = $marks['group_users'] - ($marks['full'] + $marks['incomplete'] + $marks['freezed']);
        $marks['random_achievement'] =
            User::whereIn('id', $marks['group']->leaderAndAmbassadors->pluck('id'))
            ->with(['mark' => function ($query) use ($week_id) {
                $query->where('week_id', $week_id);
                $query->with(['pendingThesisCount' => function ($query) use ($week_id) {
                    $query->whereHas('mark', function ($query) use ($week_id) {
                        $query->where('week_id', $week_id);
                    });
                }]);
            }])
            ->inRandomOrder()->limit(3)->get();

        $marks['most_read'] = Mark::where('week_id', $week_id)->whereIn('user_id',  $marks['group']->leaderAndAmbassadors->pluck('id'))->orderBy('total_pages', 'desc')->limit(5)->get();

        //support leader achievement
        $groupSupportLeader = $marks['group']->groupSupportLeader;
        if ($groupSupportLeader->isNotEmpty()) {
            $marks['support_leader_mark'] =  Auth::user()->hasAnyRole(config('constants.SUPERVISORANDABOVE_ROLES')) ?
                Mark::where('week_id', $week_id)
                ->where('user_id', $groupSupportLeader[0]->id)
                ->first() : null;
        }
        return $this->jsonResponseWithoutMessage($marks, 'data', 200);
    }

    public function MarathonReading($group_id, $week_id)
    {
        $marks['group'] = Group::with('ambassadorsInMarathon')->where('id', $group_id)->first();
        $marks['group_users'] =  $marks['group']->ambassadorsInMarathon->count();
        $marks['ambassadors_achievement'] =
            User::whereIn('id', $marks['group']->ambassadorsInMarathon->pluck('id'))
            ->with(['mark' => function ($query) use ($week_id) {
                $query->where('week_id', $week_id);
                $query->with(['pendingThesisCount' => function ($query) use ($week_id) {
                    $query->whereHas('mark', function ($query) use ($week_id) {
                        $query->where('week_id', $week_id);
                    });
                }]);
            }])->get();


        return $this->jsonResponseWithoutMessage($marks, 'data', 200);
    }
    /**
     * all ambassadors achievments.
     * @param  group_id
     * @param  week_id - filter [current - previous ]
     * @return ambassadors achievments
     */

    public function allAchievements($group_id, $week_id)
    {
        $marks['week'] = Week::find($week_id);
        $marks['group'] = Group::with('userAmbassador')->where('id', $group_id)->first();
        $marks['group_users'] = $marks['group']->userAmbassador->count() + 1;
        $marks['ambassadors_achievement'] =
            User::whereIn('id', $marks['group']->userAmbassador->pluck('id'))
            ->with(['mark' => function ($query) use ($marks, $week_id) {
                $query->where('week_id', $marks['week']->id);
                $query->with(['pendingThesisCount' => function ($query) use ($week_id) {
                    $query->whereHas('mark', function ($query) use ($week_id) {
                        $query->where('week_id', $week_id);
                    });
                }]);
            }])
            ->get();

        return $this->jsonResponseWithoutMessage($marks, 'data', 200);
    }


    /**
     * all ambassadors achievments as pages, order by total pages desc.
     *
     * @param  group _id , week id
     * @return ambassadors achievments as total pages
     */

    public function achievementAsPages($group_id, $week_id)
    {
        $week = Week::find($week_id);
        $response['group'] = Group::with('userAmbassador')->where('id', $group_id)->first();
        $response['group_users'] = $response['group']->userAmbassador->count() + 1;
        $response['ambassadors_achievement'] =
            User::whereIn('users.id', $response['group']->userAmbassador->pluck('id'))->leftJoin('marks', function ($join) use ($week) {
                $join->on('users.id', '=', 'marks.user_id')
                    ->where('marks.week_id', '=', $week->id);
            })
            ->get([
                'users.*', // Select all columns from users
                'marks.total_pages', // Select the total_pages column from marks
            ]);

        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    /**
     * ambassador achievment in a week
     *
     * @param  ambassador_name, group _id , week filter [current - previous ]
     * @return ambassador achievment
     */

    public function searchForAmbassadorAchievement($ambassador_name, $group_id, $week_filter = "current")
    {
        if ($week_filter == 'current') {
            $week = Week::latest()->pluck('id')->toArray();
        }
        if ($week_filter == 'previous') {
            $week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->pluck('id')->toArray();
        }
        $search = UserGroup::join('users', 'users.id', '=', 'user_groups.user_id')
            ->join('groups', 'groups.id', '=', 'user_groups.group_id')
            ->where('users.name', 'LIKE', "%{$ambassador_name}%")
            ->where('groups.id', $group_id)
            ->pluck('user_id')->toArray();
        $response['ambassador_achievement'] = Mark::where('week_id', $week)->whereIn('user_id',  $search)->limit(3)->get();
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }


    /**
     * search for ambassador in group
     *
     * @param  ambassador_name, group _id
     * @return ambassador achievment
     */

    public function searchForAmbassador($ambassador_name, $group_id)
    {

        $search = UserGroup::join('users', 'users.id', '=', 'user_groups.user_id')
            ->join('groups', 'groups.id', '=', 'user_groups.group_id')
            ->where('users.name', 'LIKE', "%{$ambassador_name}%")
            ->where('groups.id', $group_id)
            ->pluck('user_id')->toArray();

        //$response['users'] = User::with('groups')->whereIn('id',  $search)->get();
        $response['users'] = User::with(['groups' => function ($query) use ($group_id) {
            $query->where('groups.id', $group_id);
        }])
            // ->whereHas('groups', function ($q) use ($group_id) {
            //         $q->where('groups.id',  $group_id);
            //     })
            ->whereIn('id',  $search)->get();
        // $response['users'] = UserGroup::where('group_id', $group_id)->whereIn('user_id',  $search)->with('users')->get();

        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }



    //NEED REVIEW
    /**
     * get group audit for specific week.
     *
     * @param  group _id , week filter [current - previous ]
     * @return group audit marks
     */

    public function auditMarks($group_id, $week_filter = "current")
    {
        if ($week_filter == 'current') {
            $week = Week::latest()->pluck('id')->toArray();
        }
        if ($week_filter == 'previous') {
            $week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->pluck('id')->toArray();
        }

        $userInGroup = UserGroup::where('group_id', $group_id)
            ->where('user_id', Auth::id())->pluck('user_type')
            ->first();

        if (($userInGroup != 'ambassador' && $userInGroup != 'leader' && $userInGroup != 'support_leader')) {

            $response = Group::with('audits')->where('id', $group_id)->first();
            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    //the function will return all posts - discuss it later
    public function list_group_posts($group_id)
    {

        $group = Group::find($group_id);
        $timeLine = Timeline::find($group->timeline_id)->posts;

        if ($timeLine) {
            return $timeLine;
        } else {
            throw new NotFound;
        }
    }

    /**
     * Add a new leader request (“create RequestAmbassador” permission is required)
     *
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function createLeaderRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
            'members_num' => 'required',
            'gender' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $newRequest['members_num'] = $request->members_num;
        $newRequest['gender'] = $request->gender;
        $request['leader_id'] = Auth::id();
        if (Auth::user()->can('create RequestAmbassador')) {
            $group = Group::with('userAmbassador')->with('groupLeader')->where('id', $request->group_id)->first();
            $newRequest['current_team_count'] = $group->userAmbassador->count();
            $newRequest['leader_id'] = $group->groupLeader[0]->id;
            $leaderRequest = LeaderRequest::create($newRequest);
            return $this->jsonResponseWithoutMessage($leaderRequest, 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * get last leader ambassador request
     *
     * @param  $group id
     * @return last request;
     */

    public function lastLeaderRequest($group_id)
    {
        $group = Group::with('groupLeader')->where('id', $group_id)->first();

        if ($group) {
            $leaderRequest = LeaderRequest::where('leader_id', $group->groupLeader[0]->id)->latest()->first();
            return $this->jsonResponseWithoutMessage($leaderRequest, 'data', 200);
        } else {
            throw new NotFound;
        }
    }


    public function userGroups()
    {

        $groups = null;
        if (isset($_GET['name'])  && $_GET['name'] != '') {

            $groups = UserGroup::with('group')->with('group.users')
                ->where('user_id', auth::id())
                ->whereNull('termination_reason')
                ->whereHas('group', function ($q) {
                    $q->where('name', 'like', '%' . $_GET['name'] . '%');
                })
                ->paginate(25);
        } else {
            $groups = UserGroup::with('group')->with('group.users')
                ->where('user_id', auth::id())
                ->whereNull('termination_reason')
                ->paginate(25);
        }


        if ($groups->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'groups' => $groups,
                'total' => $groups->total(),
                'last_page' => $groups->lastPage(),
            ], 'data', 200);
        }
        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }

    public function statistics($group_id, $week_id)
    {
        $response['group'] = Group::find($group_id);
        if (!$response['group']) {
            throw new NotFound;
        }

        $response['week'] = Week::find($week_id);
        $response['weeks'] = Week::where('is_vacation', 0)->latest('id')->limit(10)->get();

        $users_in_group = $this->usersByWeek($group_id, $week_id, ['leader', 'ambassador']);


        $response['users_in_group'] = $users_in_group->count();
        $response['group_leader'] = $users_in_group->where('user_type', 'leader')->pluck('user_id');

        $response['ambassadors_reading'] = User::without('userProfile')->select('users.*')
            ->whereIn('users.id', $users_in_group->pluck('user_id'))
            ->selectRaw('COALESCE(marks.reading_mark, 0) as reading_mark')
            ->selectRaw('COALESCE(marks.writing_mark, 0) as writing_mark')
            ->selectRaw('COALESCE(marks.total_pages, 0) as total_pages')
            ->selectRaw('COALESCE(marks.total_thesis, 0) as total_thesis')
            ->selectRaw('COALESCE(marks.total_screenshot, 0) as total_screenshot')
            ->selectRaw('COALESCE(marks.support, 0) as support')
            ->selectRaw('COALESCE(marks.is_freezed, 0) as is_freezed')
            ->leftJoin('marks', function ($join) use ($week_id) {
                $join->on('users.id', '=', 'marks.user_id')
                    ->where('marks.week_id', $week_id);
            })
            ->get();

        $response['total_statistics'] = Mark::without('user,week')->where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->pluck('user_id'))
            ->where('is_freezed', 0)
            ->select(
                DB::raw('sum(reading_mark + writing_mark + support) as team_out_of_100'),
                DB::raw('sum(reading_mark) as team_reading_mark'),
                DB::raw('sum(writing_mark) as team_writing_mark'),
                DB::raw('sum(support) as team_support_mark'),
                DB::raw('sum(total_pages) as total_pages'),
                DB::raw('sum(total_thesis) as total_thesis'),
                DB::raw('sum(total_screenshot) as total_screenshot'),
            )->first();

        $response['week_avg'] = $this->groupAvg($group_id,  $response['week']->id, $users_in_group->pluck('user_id'));

        $response['most_read'] = Mark::where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->pluck('user_id'))
            ->where('is_freezed', 0)
            ->select('user_id', DB::raw('max(total_pages) as max_total_pages'))
            ->groupBy('user_id')
            ->orderBy('max_total_pages', 'desc')
            ->first();

        $response['total']['freezed'] =
            Mark::without('user,week')->where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->pluck('user_id'))
            ->where('is_freezed', 1)
            ->count();

        $response['total']['out_of_90'] = Mark::without('user')->where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->pluck('user_id'))
            ->where('is_freezed', 0)
            ->select('user_id', DB::raw('sum(reading_mark + writing_mark) as out_of_90'))
            ->groupBy('user_id')
            ->having('out_of_90', '=', 90)
            ->count();
        $response['total']['out_of_100'] = Mark::without('user')->where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->pluck('user_id'))
            ->where('is_freezed', 0)
            ->select('user_id', DB::raw('sum(reading_mark + writing_mark) as out_of_100'))
            ->groupBy('user_id')
            ->having('out_of_100', '=', 100)
            ->count();

        $response['total']['others'] = Mark::without('user')->where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->pluck('user_id'))
            ->where('is_freezed', 0)
            ->select('user_id', DB::raw('sum(reading_mark + writing_mark + support) as out_of_100'))
            ->groupBy('user_id')
            ->havingBetween('out_of_100', [10, 90])
            ->count();

        $response['total']['zero'] = $response['users_in_group'] - ($response['total']['freezed'] + $response['total']['out_of_90'] + $response['total']['out_of_100'] + $response['total']['others']);

        $currentMonth = date('m', strtotime($response['week']->created_at));
        $weeksInMonth = Week::whereRaw('MONTH(created_at) = ?', $currentMonth)->get();
        $month_achievement = Mark::without('user')->whereIn('user_id', $users_in_group->pluck('user_id'))
            ->whereIn('week_id', $weeksInMonth->pluck('id'))
            ->where('is_freezed', 0)
            ->select(DB::raw('avg(reading_mark + writing_mark + support) as out_of_100 , week_id'))
            ->groupBy('week_id')->get();

        $response['month_achievement'] = count($month_achievement) > 0 ? $month_achievement->pluck('out_of_100', 'week.title') : null;

        $response['month_achievement_title'] = Week::whereIn('id', $weeksInMonth->pluck('id'))->pluck('title')->first();

        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    /**
     * get screenshots and screens by week
     *
     * @param  $group_id,$filter
     * @return number of theses and screenshots;
     */
    public function thesesAndScreensByWeek($group_id, $filter)
    {
        $users_in_group = Group::with('leaderAndAmbassadors')->where('id', $group_id)->first();
        $week = Week::latest();
        if ($filter == 'current') {
            $week_id = $week->first()->id;
            $response = Mark::without('user,week')->where('week_id', $week_id)
                ->whereIn('user_id', $users_in_group->leaderAndAmbassadors->pluck('id'))
                ->where('is_freezed', 0)
                ->select(
                    DB::raw('sum(total_thesis) as total_thesis'),
                    DB::raw('sum(total_screenshot) as total_screenshot'),
                )->first();
        } else
        if ($filter == 'previous') {
            $week_id = $week->skip(1)->first()->id;
            $response = Mark::without('user,week')->where('week_id', $week_id)
                ->whereIn('user_id', $users_in_group->leaderAndAmbassadors->pluck('id'))
                ->where('is_freezed', 0)
                ->select(
                    DB::raw('sum(total_thesis) as total_thesis'),
                    DB::raw('sum(total_screenshot) as total_screenshot'),
                )->first();
        } else
        if ($filter == 'in_a_month') {
            $currentMonth = $week->first()->created_at->format('m');
            $week = Week::whereRaw('MONTH(created_at) = ?', [$currentMonth])->pluck('id')->toArray();
            $response = Mark::without('user,week')->whereIn('week_id', $week)
                ->whereIn('user_id', $users_in_group->leaderAndAmbassadors->pluck('id'))
                ->where('is_freezed', 0)
                ->select(
                    DB::raw('sum(total_thesis) as total_thesis'),
                    DB::raw('sum(total_screenshot) as total_screenshot'),
                )->first();
        }
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    /**
     * get group month achievement
     *
     * @param  $group_id,$filter
     * @return month achievement;
     */
    public function monthAchievement($group_id, $filter)
    {
        $week = Week::latest()->first();
        if ($filter == 'current') {
            // $currentMonth = date('m');
            $currentMonth = date('m', strtotime($week->created_at));
        } else
        if ($filter == 'previous') {
            // $currentMonth = date('m') - 1;
            $currentMonth = date('m', strtotime($week->created_at)) - 1;
        }
        $users_in_group = Group::with('leaderAndAmbassadors')->where('id', $group_id)->first();

        $weeksInMonth = Week::whereRaw('MONTH(created_at) = ?', [$currentMonth])->get();

        if ($weeksInMonth->isEmpty()) {
            throw new NotFound;
        }

        $month_achievement = Mark::without('user')->whereIn('user_id', $users_in_group->leaderAndAmbassadors->pluck('id'))
            ->whereIn('week_id', $weeksInMonth->pluck('id'))
            ->where('is_freezed', 0)
            ->select(DB::raw('avg(reading_mark + writing_mark + support) as out_of_100 , week_id'))
            ->groupBy('week_id')->get();

        $response['month_achievement'] =  $month_achievement->pluck('out_of_100', 'week.title');

        $response['month_achievement_title'] = Week::whereIn('id', $weeksInMonth->pluck('id'))->pluck('title')->first();
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }



    public function assignAdministrator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required',
            'user' => 'required|email',
            'user_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        try {
            $arabicRole = config('constants.ARABIC_ROLES')[$request->user_type];

            //check if user have role of the selecte user_type
            $user = User::where('email', $request->user)->first();
            if ($user) {
                if ($user->hasRole($request->user_type)) {
                    //get all user ambassadors [in the advising group => they are a supervisors in other groups]
                    $group = Group::with('userAmbassador')->where('id', $request->group_id)->first();

                    if ($group) {
                        $groupName = $group->name;

                        //add user to selected advising group
                        UserGroup::updateOrCreate(
                            [
                                'user_type' => $request->user_type,
                                'group_id' => $group->id
                            ],
                            [
                                'user_id' => $user->id
                            ]
                        );

                        // get groups for each supervisor and add advisor
                        foreach ($group->userAmbassador as $supervisor) {
                            // get groups for each supervisor

                            $supervisor_groups = UserGroup::where('user_id', $supervisor->id)
                                ->where(function ($query) {
                                    $query->where('user_type', 'supervisor')
                                        ->orWhere('user_type', 'advisor');
                                })
                                ->whereNull('termination_reason')->get();

                            //add advisor
                            if ($supervisor_groups->isNotEmpty()) {
                                foreach ($supervisor_groups as $group) {
                                    UserGroup::updateOrCreate(
                                        [
                                            'user_type' => $request->user_type,
                                            'group_id' => $group->group_id
                                        ],
                                        [
                                            'user_id' => $user->id
                                        ]
                                    );
                                }
                            }
                        }

                        $logInfo = ' قام ' . Auth::user()->name . " بتعيين " . $user->name . ' على أفرقة ' . $groupName . ' بدور '  . $arabicRole;
                        Log::channel('community_edits')->info($logInfo);

                        return $this->jsonResponseWithoutMessage("تمت الاضافة", 'data', 200);
                    } else {
                        return $this->jsonResponseWithoutMessage("المجموعة غير موجودة", 'data', 200);
                    }
                } else {
                    return $this->jsonResponseWithoutMessage("قم بترقية العضو ل" . $arabicRole . " أولاً", 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("المستخدم غير موجود", 'data', 200);
            }
        } catch (\Exception $e) {
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }


    public function assignSupervisor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'newSupervisor' => 'required|email',
            'group_id'  => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->hasanyrole('admin|advisor|consultant')) {

            $newSupervisor = User::where('email', $request->newSupervisor)->first();
            if ($newSupervisor) {

                $currentSupervising = UserGroup::where('user_id',  $newSupervisor->id)->where('user_type', 'supervisor')->whereNull('termination_reason')
                    ->whereHas('group.type', function ($q) {
                        $q->where('type', '=', 'supervising');
                    })->first();

                // if not supervisor in any group
                if (!$currentSupervising) {
                    $group = Group::with('userAmbassador')->where('id', $request->group_id)->first();
                    if ($group) {
                        $groupName = $group->name;
                        //* اضافة المراقب الجديد إلى مجموعة الرقابة

                        UserGroup::updateOrCreate(
                            [
                                'group_id' => $group->id,
                                'user_type' => "supervisor"
                            ],
                            [
                                'user_id' => $newSupervisor->id,
                            ]
                        );
                        foreach ($group->userAmbassador as $leader) {
                            User::where("id", $leader->id)->update(["parent_id" => $newSupervisor->id]);
                            UserParent::where("user_id", $leader->id)->update(["is_active" => 0]);
                            UserParent::create([
                                'user_id' => $leader->id,
                                'parent_id' =>  $newSupervisor->id,
                                'is_active' => 1,
                            ]);

                            $followupGroup = UserGroup::where('user_id',  $leader->id)->where('user_type', 'leader')->whereNull('termination_reason')
                                ->whereHas('group.type', function ($q) {
                                    $q->where('type', '=', 'followup');
                                })->first();
                            //* اضافة المراقب الجديد إلى مجموعة المتابعة
                            UserGroup::updateOrCreate(
                                [
                                    'group_id' => $followupGroup->group_id,
                                    'user_type' => "supervisor"
                                ],
                                [
                                    'user_id' => $newSupervisor->id,
                                ]
                            );
                            // //* اضافة الموجه الجديد إلى مجموعة المتابعة
                            // UserGroup::updateOrCreate(
                            //     [
                            //         'group_id' => $followupGroup->id,
                            //         'user_type' => "advisor"
                            //     ],
                            //     [
                            //         'user_id' => $newSupervisor->parent_id,
                            //     ]
                            // );
                        }
                        $logInfo = ' قام ' . Auth::user()->name . " بتعيين " . $newSupervisor->name . ' على أفرقة ' . $groupName . ' بدور مراقب';
                        Log::channel('community_edits')->info($logInfo);

                        return $this->jsonResponseWithoutMessage('تمت الاضافة', 'data', 200);
                    } else {
                        return $this->jsonResponseWithoutMessage('الفريق الرقابي غير موجود', 'data', 200);
                    }
                } else {
                    return $this->jsonResponseWithoutMessage('المراقب موجود كمراقب في مجموعة أخرى', 'data', 200);
                }
            } else {
                return $this->jsonResponseWithoutMessage("المراقب  غير موجود", 'data', 200);
            }
        } else {
            throw new NotAuthorized;
        }
    }

    public function getMarathonParticipants()
    {
        $participants = Group::whereHas('type', function ($q) {
            $q->where('type', 'marathon');
        })->with('users')->paginate(10);
        if ($participants->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'participants' => $participants,
                'total' => $participants->total(),
                'last_page' => $participants->lastPage(),
            ], 'data', 200);
        }
        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }

    public function currentAmbassadorsCount($id)
    {
        $ambassadorsCount = Group::with('type')->withCount('userAmbassador')->where('id', $id)->first();
        return $this->jsonResponseWithoutMessage($ambassadorsCount, 'data', 200);
    }
}
