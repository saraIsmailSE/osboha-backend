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
use App\Models\TimelineType;

/**
 * Description: GroupController for Osboha group.
 *
 * Methods: 
 * - CRUD
 * - group posts list
 */

class GroupController extends Controller
{

    use ResponseJson, MediaTraits;


    public function index()
    {
        $group = Group::withCount('users')->get();
        if (Auth::user()->can('list groups')) {
            return $this->jsonResponseWithoutMessage($group, 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }
    public function GroupByType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (Auth::user()->can('list groups')) {

            $groups = Group::where('type_id', $request->type_id)->get();
            if ($groups->isNotEmpty()) {
                return $this->jsonResponseWithoutMessage(GroupResource::collection($groups), 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
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

        $response['info'] = Group::with('users', 'groupAdministrators')->withCount('userAmbassador')->where('id', $group_id)->first();
        if ($response['info']) {
            $response['authInGroup'] = UserGroup::where('user_id', Auth::id())->where('group_id', $group_id)->first();
            if ($response['authInGroup'] || Auth::user()->hasRole('admin')) {

                //group posts
                $response['post'] = Timeline::find($response['info']->id);

                //week avg
                $response['week'] = Week::latest('id')->first();

                $response['week_avg'] = Mark::where('week_id', $response['week']->id)
                    ->whereIn('user_id', $response['info']->users->pluck('id'))
                    //avg from (reading_mark + writing_mark + support)
                    ->select(DB::raw('avg(reading_mark + writing_mark + support) as out_of_100'))
                    ->first()
                    ->out_of_100;
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

    public function update(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'group_id' => 'required',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'type_id' => 'required',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $group = Group::find($request->group_id);
        $user_type = UserGroup::where('group_id', $request->group_id)->where('user_id', Auth::id())->pluck('user_type')->first();
        if ($group) {

            if (Auth::user()->can('edit group') && $user_type != "ambassador") {
                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $currentMedia = Media::where('group_id', $group->id);

                    if ($currentMedia) {
                        $this->updateMedia($file, $currentMedia->id);
                    } else {
                        $this->createMedia($file, $group->id, 'group');
                    }
                }

                $group->update($input);
                return $this->jsonResponseWithoutMessage("Group Updated", 'data', 200);
            } //endif Auth

            else {
                throw new NotAuthorized;
            }
        } //end if group found

        else {
            throw new NotFound;
        }
    }

    public function delete($group_id)
    {
        if (Auth::user()->can('delete group')) {
            $group = Group::find($group_id);
            if ($group) {

                $currentMedia = Media::where('group_id', $group->id)->first();

                //if exist delete image
                if ($currentMedia) {
                    $this->deleteMedia($currentMedia->id);
                }

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

        $userInGroup = UserGroup::where('group_id', $group_id)
            ->where('user_id', Auth::id())->pluck('user_type')
            ->first();

        if ($userInGroup != 'ambassador') {
            $response['week'] = Week::latest()->first();
            $response['group'] = Group::with('userAmbassador')->where('id', $group_id)->first();
            $response['exceptions'] = UserException::whereIn('user_id', $response['group']->userAmbassador->pluck('id'))->latest()->get();
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

        $group = Group::with('users')->where('id', $group_id)->first();
        if ($filter == 'oldest') {
            $exceptions = UserException::whereIn('user_id', $group->users->pluck('id'))->get();
        } else if ($filter == 'latest') {
            $exceptions = UserException::whereIn('user_id', $group->users->pluck('id'))->latest()->get();
        } else if ($filter == 'freez') {
            $exceptions = UserException::whereHas('type', function ($query) {
                $query->where('type', 'تجميد الأسبوع الحالي')
                    ->orWhere('type', 'تجميد الأسبوع القادم');
            })->whereIn('user_id', $group->users->pluck('id'))->latest()->get();
        } else if ($filter == 'exceptional_freez') {
            $exceptions = UserException::whereHas('type', function ($query) {
                $query->where('type', 'تجميد استثنائي');
            })->whereIn('user_id', $group->users->pluck('id'))->latest()->get();
        } else if ($filter == 'exams') {
            $exceptions = UserException::whereHas('type', function ($query) {
                $query->where('type', 'نظام امتحانات - شهري')
                    ->orWhere('type', 'نظام امتحانات - فصلي');
            })->whereIn('user_id', $group->users->pluck('id'))->latest()->get();
        } else if ($filter == 'accepted') {
            $exceptions = UserException::where('status', 'accepted')->whereIn('user_id', $group->users->pluck('id'))->latest()->get();
        } else if ($filter == 'pending') {
            $exceptions = UserException::where('status', 'pending')->whereIn('user_id', $group->users->pluck('id'))->latest()->get();
        } else if ($filter == 'rejected') {
            $exceptions = UserException::where('status', 'rejected')->whereIn('user_id', $group->users->pluck('id'))->latest()->get();
        } else if ($filter == 'finished') {
            $exceptions = UserException::where('status', 'finished')->whereIn('user_id', $group->users->pluck('id'))->latest()->get();
        }

        return $this->jsonResponseWithoutMessage($exceptions, 'data', 200);
    }

    /**
     * Basic group marks.
     * 
     * @param  group _id
     * @return group info , week satistics [100 - 0 -incomplete - most read]
     */
    public function BasicMarksView($group_id)
    {
        $marks['group'] = Group::with('leaderAndAmbassadors')->where('id', $group_id)->first();
        $current_week = Week::latest()->pluck('id')->first();
        $marks['group_users'] =  $marks['group']->leaderAndAmbassadors->count();

        $marks['full'] = Mark::where('week_id', $current_week)
            ->whereIn('user_id',  $marks['group']->leaderAndAmbassadors->pluck('id'))
            ->select(DB::raw('(reading_mark + writing_mark + support) as out_of_100'))
            ->having('out_of_100', 100)
            ->count();

        $marks['incomplete'] = Mark::where('week_id', $current_week)
            ->whereIn('user_id',  $marks['group']->leaderAndAmbassadors->pluck('id'))
            ->select(DB::raw('(reading_mark + writing_mark + support) as out_of_100'))
            ->having('out_of_100', '<', 100)
            ->having('out_of_100', '>', 0)
            ->count();

        $marks['zero'] = Mark::where('week_id', $current_week)
            ->whereIn('user_id',  $marks['group']->leaderAndAmbassadors->pluck('id'))
            ->select(DB::raw('(reading_mark + writing_mark + support) as out_of_100'))
            ->having('out_of_100', 0)
            ->count();
        $marks['random_achievement'] = Mark::where('week_id', $current_week)->whereIn('user_id',  $marks['group']->leaderAndAmbassadors->pluck('id'))->inRandomOrder()->limit(3)->get();
        $marks['most_read'] = Mark::where('week_id', $current_week)->whereIn('user_id',  $marks['group']->leaderAndAmbassadors->pluck('id'))->orderBy('total_pages', 'desc')->limit(5)->get();


        return $this->jsonResponseWithoutMessage($marks, 'data', 200);
    }

    /**
     * all ambassadors achievments.
     * 
     * @param  group _id , week filter [current - previous ]
     * @return ambassadors achievments
     */

    public function allAchievements($group_id, $week_filter = "current")
    {
        if ($week_filter == 'current') {
            $week = Week::latest()->pluck('id')->toArray();
        }
        if ($week_filter == 'previous') {
            $week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->pluck('id')->toArray();
        }

        $marks['group'] = Group::with('userAmbassador')->where('id', $group_id)->first();
        $marks['group_users'] = $marks['group']->userAmbassador->count() +1;
        $marks['ambassadors_achievement'] = Mark::where('week_id', $week)->whereIn('user_id', $marks['group']->userAmbassador->pluck('id'))->get();

        return $this->jsonResponseWithoutMessage($marks, 'data', 200);
    }


    /**
     * all ambassadors achievments as pages, order by total pages desc.
     * 
     * @param  group _id , week filter [current - previous ]
     * @return ambassadors achievments as total pages
     */

    public function achievementAsPages($group_id, $week_filter = "current")
    {
        if ($week_filter == 'current') {
            $week = Week::latest()->pluck('id')->toArray();
        }
        if ($week_filter == 'previous') {
            $week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->pluck('id')->toArray();
        }

        $response['group'] = Group::with('userAmbassador')->where('id', $group_id)->first();
        $response['group_users'] = $response['group']->userAmbassador->count() +1;
        $response['ambassadors_achievement'] = Mark::where('week_id', $week)->whereIn('user_id',  $response['group']->userAmbassador->pluck('id'))->orderBy('total_pages', 'desc')->get();
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
        $response['groups'] = UserGroup::with('group')->with('group.users')->where('user_id', auth::id())->whereNull('termination_reason')->get();

        if (!$response['groups']->isEmpty()) {
            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    public function statistics($group_id, $week_filter = "current")
    {
        $group = Group::find($group_id);
        if (!$group) {
            throw new NotFound;
        }

        $response['week'] = Week::latest()->first();
        $users_in_group = Group::with('leaderAndAmbassadors')->where('id', $group_id)->first();
        $response['users_in_group'] = $users_in_group->count();

        $response['total_statistics'] = Mark::without('user,week')->where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->leaderAndAmbassadors->pluck('id'))
            ->where('is_freezed', 0)
            ->select(
                DB::raw('avg(reading_mark + writing_mark + support) as team_out_of_100'),
                DB::raw('avg(reading_mark) as team_reading_mark'),
                DB::raw('avg(writing_mark) as team_writing_mark'),
                DB::raw('avg(support) as team_support_mark'),
                DB::raw('sum(total_pages) as total_pages'),
                DB::raw('sum(total_thesis) as total_thesis'),
                DB::raw('sum(total_screenshot) as total_screenshot'),
            )->first();

        $response['most_read'] = Mark::where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->leaderAndAmbassadors->pluck('id'))
            ->where('is_freezed', 0)
            ->select('user_id', DB::raw('max(total_pages) as max_total_pages'))
            ->groupBy('user_id')
            ->orderBy('max_total_pages', 'desc')
            ->first();

        $response['total']['freezed'] = Mark::without('user')->where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->leaderAndAmbassadors->pluck('id'))
            ->where('is_freezed', 1)
            ->count();
        $response['total']['zero'] = Mark::without('user')->where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->leaderAndAmbassadors->pluck('id'))
            ->where('is_freezed', 0)
            ->select('user_id', DB::raw('sum(reading_mark + writing_mark + support) as out_of_100'))
            ->groupBy('user_id')
            ->having('out_of_100', '=', 0)
            ->count();
        $response['total']['out_of_90'] = Mark::without('user')->where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->leaderAndAmbassadors->pluck('id'))
            ->where('is_freezed', 0)
            ->select('user_id', DB::raw('sum(reading_mark + writing_mark) as out_of_90'))
            ->groupBy('user_id')
            ->having('out_of_90', '=', 90)
            ->count();
        $response['total']['out_of_100'] = Mark::without('user')->where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->leaderAndAmbassadors->pluck('id'))
            ->where('is_freezed', 0)
            ->select('user_id', DB::raw('sum(reading_mark + writing_mark) as out_of_100'))
            ->groupBy('user_id')
            ->having('out_of_100', '=', 100)
            ->count();

        $response['total']['others'] = Mark::without('user')->where('week_id', $response['week']->id)
            ->whereIn('user_id', $users_in_group->leaderAndAmbassadors->pluck('id'))
            ->where('is_freezed', 0)
            ->select('user_id', DB::raw('sum(reading_mark + writing_mark + support) as out_of_100'))
            ->groupBy('user_id')
            ->havingBetween('out_of_100', [10, 90])
            ->count();

        $currentMonth = date('m', strtotime($response['week']->created_at));
        $weeksInMonth = Week::whereRaw('MONTH(created_at) = ?', $currentMonth)->get();
        $month_achievement = Mark::without('user')->whereIn('user_id', $users_in_group->leaderAndAmbassadors->pluck('id'))
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
}