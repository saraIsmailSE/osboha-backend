<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mark;
use App\Models\AuditMark;
use App\Models\UserGroup;
use App\Models\Group;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\MarkResource;
use App\Models\User;
use App\Models\Week;
use App\Events\MarkStats;
use App\Http\Resources\CommentResource;
use App\Http\Resources\ReactionTypeResource;
use App\Models\AssignSupport;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostType;
use App\Models\Thesis;
use App\Notifications\GeneralNotification;
use App\Notifications\MailSupportPost;
use App\Traits\PathTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarkController extends Controller
{
    use ResponseJson, PathTrait;

    /**
     * Read all  marks in the current week in the system(“audit mark” permission is required)
     *
     * @return jsonResponseWithoutMessage;
     */
    public function index()
    {
        if (Auth::user()->can('audit mark')) {
            $current_week = Week::latest()->first();
            $marks = Mark::where('week_id', $current_week->id)->get();

            if ($marks) {
                return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * Update an existing mark ( “edit mark” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'out_of_90' => 'required',
            'out_of_100' => 'required',
            // 'total_pages' => 'required',
            // 'support' => 'required',
            'total_thesis' => 'required',
            // 'total_screenshot' => 'required',
            'mark_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (!Auth::user()->can('edit mark')) {
            $mark = Mark::find($request->mark_id);
            $old_mark = $mark->getOriginal();
            if ($mark) {
                $mark->update($request->all());
                event(new MarkStats($mark, $old_mark));
                return $this->jsonResponseWithoutMessage("Mark Updated Successfully", 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
    /**
     * Return list of user mark ( audit mark” permission is required OR request user_id == Auth).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function list_user_mark(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required_without:week_id',
            'week_id' => 'required_without:user_id'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if ((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
            && $request->has('week_id') && $request->has('user_id')
        ) {
            $marks = Mark::where('user_id', $request->user_id)
                ->where('week_id', $request->week_id)->get();
            if ($marks) {
                return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data', 200);
            } else {
                throw new NotFound;
            }
        } else if ((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
            && $request->has('week_id')
        ) {
            $marks = Mark::where('week_id', $request->week_id)->get();
            if ($marks) {
                return $this->jsonResponseWithoutMessage(MarkResource::collection($marks), 'data', 200);
            } else {
                throw new NotFound;
            }
        } else if ((Auth::user()->can('audit mark') || $request->user_id == Auth::id())
            && $request->has('user_id')
        ) {
            $mark = Mark::where('user_id', $request->user_id)->latest()->first();
            if ($mark) {
                return $this->jsonResponseWithoutMessage(new MarkResource($mark), 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     *  Return all leader marks for auth auditor in current week.
     *
     *  @return jsonResponseWithoutMessage;
     */
    public function leadersAuditmarks()
    {
        if (Auth::user()->can('audit mark')) {
            $current_week = Week::latest()->pluck('id')->first();
            $leadersAuditMarksId = AuditMark::where('auditor_id', Auth::id())
                ->where('week_id', $current_week)
                ->pluck('leader_id');
            if ($leadersAuditMarksId) {
                $leadersMark = Mark::whereIn('user_id', $leadersAuditMarksId)
                    ->where('week_id', $current_week)
                    ->get();
                return $this->jsonResponseWithoutMessage(MarkResource::collection($leadersMark), 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
    /**
     *  Return audit marks & note & status for a specific leader in current week
     *  by leader_id with “audit mark” permission.
     *
     *  @return jsonResponseWithoutMessage;
     */
    public function showAuditmarks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leader_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->can('audit mark')) {
            $current_week = Week::latest()->pluck('id')->first();
            $auditMarksId = AuditMark::where('leader_id', $request->leader_id)
                ->where('week_id', $current_week)
                ->where('auditor_id', Auth::id())
                ->pluck('id')
                ->first();

            if ($auditMarksId) {
                $auditMarksIds = AuditMark::where('id', $auditMarksId)
                    ->pluck('aduitMarks')
                    ->first();

                $note = AuditMark::select('note', 'status')
                    ->where('id', $auditMarksId)
                    ->first();

                $marksId = unserialize($auditMarksIds);
                $aduitMarks = Mark::whereIn('id', $marksId)->get();
                $aduitMarks = MarkResource::collection($aduitMarks);
                $marksAndNote = $aduitMarks->merge($note);

                return $this->jsonResponseWithoutMessage($marksAndNote, 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }


    /**
     * Update note and status for existing audit marks by its id with “audit mark” permission.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function updateAuditMark(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'auditMark_id' => 'required',
            'note' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->can('audit mark')) {
            $auditMarks = AuditMark::where('id', $request->auditMark_id)->first();
            if ($auditMarks) {
                $auditMarks->note = $request->note;
                $auditMarks->status = $request->status;
                $auditMarks->update();
                return $this->jsonResponseWithoutMessage("Audit Mark Updated Successfully", 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * get user month achievement
     *
     * @param  $user_id,$filter
     * @return month achievement;
     */
    public function userMonthAchievement($user_id, $filter)
    {
        $week = Week::latest()->first();

        if ($filter == 'current') {
            $currentMonth = \Carbon\Carbon::now()->format('m');
        } else
        if ($filter == 'previous') {
            $currentMonth = \Carbon\Carbon::parse($week->created_at)->subMonth()->format('m');
        }

        $weeksInMonth = Week::whereRaw('MONTH(created_at) = ?', [$currentMonth])->get();

        if ($weeksInMonth->isEmpty()) {
            throw new NotFound;
        }

        $response['month_achievement'] = Mark::where('user_id', $user_id)
            ->whereIn('week_id', $weeksInMonth->pluck('id'))
            ->select(DB::raw('avg(reading_mark + writing_mark + support) as out_of_100 , week_id'))
            ->groupBy('week_id')
            ->get()
            ->pluck('out_of_100', 'week.title');

        $response['month_achievement_title'] = Week::whereIn('id', $weeksInMonth->pluck('id'))->pluck('title')->first();
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }
    /**
     * get user week achievement
     *
     * @param  $user_id,$filter
     * @return month achievement;
     */
    public function userWeekAchievement($user_id, $filter)
    {
        //current week
        $week =  Week::latest();
        if ($filter == 'current') {

            $response['week_mark'] = Mark::where('week_id', $week->first()->id)->where('user_id', $user_id)->first();
        } else
        if ($filter == 'previous') {
            $previousWeek = $week->skip(1)->first()->id;
            $response['week_mark'] = Mark::where('week_id', $previousWeek)->where('user_id', $user_id)->first();
        } else
        if ($filter == 'in_a_month') {
            $currentMonth = $week->first()->created_at->format('m');
            $week = Week::whereRaw('MONTH(created_at) = ?', [$currentMonth])->pluck('id')->toArray();
            $response['week_mark'] = Mark::whereIn('week_id', $week)->where('user_id', $user_id)
                ->select(DB::raw('sum(total_thesis) as total_thesis , sum(total_screenshot) as total_screenshot'))
                ->first();
        }
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    /**
     * get user mark with theses => list only for group administrators
     * with support done by the user
     *
     * @param  $user_id
     * @return mark;
     */
    public function ambassadorMark($user_id, $week_id)
    {
        $user = User::find($user_id);
        if ($user) {
            $user_group = UserGroup::where('user_id', $user_id)->where('user_type', 'ambassador')->whereNull('termination_reason')->first();
            if ($user_group) {
                $response['group'] = Group::where('id', $user_group->group_id)->with('groupAdministrators')->first();

                //check if the user is support leader
                $isSupportLeader = UserGroup::where('user_id', $user->id)->where('user_type', 'support_leader')->first();
                $supportLeaderGroupAdmins = $isSupportLeader ? $isSupportLeader->group->groupAdministrators->pluck('id')->toArray() : [];
                $isAuthAdminInGroup = in_array(Auth::id(), $supportLeaderGroupAdmins);

                if (
                    (in_array(Auth::id(), $response['group']->groupAdministrators->pluck('id')->toArray())) ||
                    Auth::user()->hasRole('admin') || $isAuthAdminInGroup
                ) {

                    $currentWeek = Week::find($week_id);
                    $response['currentWeek'] = $currentWeek;
                    $response['mark'] = Mark::where('user_id', $user_id)->where('week_id', $response['currentWeek']->id)->first();
                    $response['theses'] = Thesis::with('book')->where('mark_id',  $response['mark']->id)->get();

                    //check if the auth user can take action if the user is support leader
                    if ($isSupportLeader) {
                        $supportLeaderParent = $isSupportLeader->user->parent;
                        $parentGroup = UserGroup::where('user_id', $supportLeaderParent->id)->where('user_type', 'leader')->first();
                        $parentGroupAdmins = $parentGroup ? $parentGroup->group->groupAdministrators->pluck('id')->toArray() : [];
                        $isAuthAdminInGroups = in_array(Auth::id(), $parentGroupAdmins);
                        $response['can_edit'] = $isAuthAdminInGroups;
                    } else {
                        $response['can_edit'] = true;
                    }

                    /*support -- asmaa*/
                    $main_timer = $currentWeek->main_timer;
                    $PostTypeSupport = PostType::where('type', 'support')->first()->id;
                    $support_post = Post::where('type_id', $PostTypeSupport)
                        ->where('created_at', '>', $currentWeek->created_at)
                        ->where('created_at', '<', $main_timer)
                        ->latest()
                        ->first();

                    if ($support_post) {
                        $pollVote = null;

                        if ($support_post->pollOptions->count() > 0) {
                            $pollVote = $support_post->pollVotes->where('user_id', $user_id)->first();
                        }

                        $user_comments = Comment::where('user_id', $user_id)
                            ->where('post_id', $support_post->id)
                            // ->where('comment_id', 0)
                            ->where('created_at', '>', $currentWeek->created_at)
                            ->where('created_at', '<', $main_timer)
                            // ->with('replies', function ($query) use ($user_id) {
                            //     $query->where('user_id', $user_id);
                            // })
                            ->get();

                        $reaction = $support_post->reactions->where('user_id', $user_id)->first();

                        $response['support'] = [
                            'post_id' => $support_post->id,
                            'comments' => CommentResource::collection($user_comments),
                            'vote' => $pollVote ? $pollVote->pollOption : null,
                            'reaction' => $reaction ? new ReactionTypeResource($reaction->type) : null,
                            'supportError' => null
                        ];
                    } else {
                        $response['support']['supportError'] = 'لم يتم نشر منشور اعرف مشروعك بعد!';
                    }
                    /*end support*/
                    return $this->jsonResponseWithoutMessage($response, 'data', 200);
                } else {
                    throw new NotAuthorized;
                }
            } else {
                return $this->jsonResponseWithoutMessage('ليس سفيرا في اية مجموعة', 'data', 404);
            }
        } else {
            throw new NotFound;
        }
    }

    public function marathonAmbassadorMark($user_id, $week_id)
    {
        $user = User::find($user_id);
        if ($user) {
            $user_group = UserGroup::where('user_id', $user_id)->where('user_type', 'marathon_ambassador')->whereNull('termination_reason')->first();
            if ($user_group) {
                $response['group'] = Group::where('id', $user_group->group_id)->with('groupAdministrators')->first();
                if (
                    (in_array(Auth::id(), $response['group']->groupAdministrators->pluck('id')->toArray())) ||
                    Auth::user()->hasRole('admin')
                ) {

                    $currentWeek = Week::find($week_id);
                    $response['currentWeek'] = $currentWeek;
                    $response['mark'] = Mark::where('user_id', $user_id)->where('week_id', $response['currentWeek']->id)->first();
                    $response['theses'] = Thesis::with('book')->where('mark_id',  $response['mark']->id)->get();


                    /*end support*/
                    return $this->jsonResponseWithoutMessage($response, 'data', 200);
                } else {
                    throw new NotAuthorized;
                }
            } else {
                return $this->jsonResponseWithoutMessage('ليس سفيرا في اية مجموعة', 'data', 404);
            }
        } else {
            throw new NotFound;
        }
    }
    /**
     * accept support vote for ambassador
     * @param  $user_id
     * @return String;
     * @return NotFound;
     * @return NotAuthorized;
     * @todo check achievement from thesis
     */
    public function acceptSupport($user_id, $week_id)
    {
        //get user group and its administrators
        $group = UserGroup::with('group.groupAdministrators')->where('user_id', $user_id)->where('user_type', 'ambassador')->whereNull('termination_reason')->first();
        //check if auth user is an administrator in the group
        if ($group && $group->group->groupAdministrators->contains('id', Auth::id())) {
            $week = Week::find($week_id);
            if ($week) {
                if (date('Y-m-d H:i:s') > $week->modify_timer) {
                    return $this->jsonResponseWithoutMessage("لا يمكنك اضافة العلامة, لقد انتهى الأسبوع", 'data', Response::HTTP_NOT_ACCEPTABLE);
                }
                $mark = Mark::where('week_id', $week_id)->where('user_id', $user_id)->first();
                if ($mark) {
                    $mark->update(['support' => 10]);
                    return $this->jsonResponseWithoutMessage("Mark Updated Successfully", 'data', 200);
                } else {
                    throw new NotFound;
                }
            } else {
                return $this->jsonResponseWithoutMessage("Week Not Found", 'data', 200);
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * reject support vote for ambassador
     * @param  $user_id
     * @return String;
     * @todo check achievment from thesis
     */
    public function rejectSupport($user_id, $week_id)
    {
        $group = UserGroup::with('group.groupAdministrators')->where('user_id', $user_id)->where('user_type', 'ambassador')->whereNull('termination_reason')->first();
        //check if auth user is an administrator in group
        if ($group && $group->group->groupAdministrators->contains('id', Auth::id())) {
            $week = Week::find($week_id);
            if ($week) {
                if (date('Y-m-d H:i:s') > $week->modify_timer) {
                    return $this->jsonResponseWithoutMessage("لا يمكنك رفض العلامة, لقد انتهى الأسبوع", 'data', Response::HTTP_NOT_ACCEPTABLE);
                }
                $mark = Mark::where('week_id', $week_id)->where('user_id', $user_id)->first();
                if ($mark) {
                    $mark->update(['support' => 0]);

                    //notify ambassador
                    $userToNotify = User::findOrFail($user_id);
                    // with email
                    $userToNotify->notify((new MailSupportPost($userToNotify->name)));
                    // with notification
                    $msg = "لقد تم رفض تصويتك على منشور الدعم لهذا الاسبوع, تفقد المنشور لتعديله";
                    (new NotificationController)->sendNotification($user_id, $msg, GROUPS, $this->getSuportPostPath());
                    return $this->jsonResponseWithoutMessage("support Mark Updated Successfully", 'data', 200);
                } else {
                    throw new NotFound;
                }
            } else {
                return $this->jsonResponseWithoutMessage("Week Not Found", 'data', 200);
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * set support mark for all active users
     * @param Request $request -> reason
     * @return String;
     */

    public function setSupportMarkForAll(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'reason' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }


        if (Auth::user()->hasRole('admin') || Auth::user()->hasRole('consultant')) {
            $current_week = Week::latest()->pluck('id')->first();

            AssignSupport::updateOrCreate(
                ['week_id' => $current_week],
                ['week_id' => $current_week, 'reason' => $request->reason, 'user_id' => Auth::id()]
            );


            Mark::where('week_id', $current_week)->update(['support' => 10]);
            $sender = User::find(Auth::id()) ?? User::find(1);
            $message = "تم اعتماد علامة اعرف مشروعك للجميع";
            $users = User::where('is_excluded', 0)->get();
            Notification::send($users, new GeneralNotification($sender, $message, 'SUPPORT_MARK'));

            return $this->jsonResponseWithoutMessage("تم الاعتماد", 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }


    /*
    # Top Users by Month Endpoint Documentation

    This endpoint retrieves statistics for the top users based on the maximum values of total_pages and total_thesis recorded within the current month.

    ## Endpoint

    `GET /top-users-by-month`

    ## Parameters

    None

    ## Response

    A successful response will return a JSON object containing the maximum values of total_pages and total_thesis recorded within the current month.

    ## Errors

    - `500 Internal Server Error`: If an unexpected error occurs during processing.
    */


    public function topUsersByMonth()
    {
        $lastMonth = Carbon::now()->subMonth();
        $startOfMonth = strval($lastMonth->startOfMonth());
        $endOfMonth = strval($lastMonth->endOfMonth());

        $response['max_total_pages'] = Cache::remember('max_total_pages_in_month', now()->addHours(24), function () use ($startOfMonth, $endOfMonth) {
            return Mark::with('user')
                ->where('is_freezed', 0)
                ->select('user_id', DB::raw('max(total_pages) as max_total_pages'))
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->groupBy('user_id')
                ->orderBy('max_total_pages', 'desc')
                ->limit(53)->get();
        });
        $response['max_total_thesis'] = Cache::remember('max_total_thesis_in_month', now()->addHours(24), function () use ($startOfMonth, $endOfMonth) {
            return Mark::with('user')
                ->where('is_freezed', 0)
                ->select('user_id', DB::raw('max(total_thesis) as max_total_thesis'))
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->groupBy('user_id')
                ->orderBy('max_total_thesis', 'desc')
                ->limit(53)->get();
        });

        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    /*
    # Top Users by Week Endpoint Documentation

    This endpoint retrieves statistics for the top users based on the maximum values of total_pages and total_thesis recorded within the previous week.

    ## Endpoint

    `GET /top-users-by-week`

    ## Parameters

    None

    ## Response

    A successful response will return a JSON object containing the maximum values of total_pages and total_thesis recorded within the previous week.

    ## Errors

    - `500 Internal Server Error`: If an unexpected error occurs during processing.
    */
    public function topUsersByWeek()
    {
        $response['previous_week'] = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();



        $response['max_total_pages'] = Cache::remember('max_total_pages_in_week', now()->addHours(24), function () use ($response) {
            return Mark::with('user')
                ->where('is_freezed', 0)
                ->select('user_id', DB::raw('max(total_pages) as max_total_pages'))
                ->where('week_id', $response['previous_week']->id)
                ->groupBy('user_id')
                ->orderBy('max_total_pages', 'desc')
                ->limit(53)->get();
        });

        $response['max_total_thesis'] = Cache::remember('max_total_thesis_in_week', now()->addHours(24), function () use ($response) {
            return Mark::with('user')
                ->where('is_freezed', 0)
                ->select('user_id', DB::raw('max(total_thesis) as max_total_thesis'))
                ->where('week_id', $response['previous_week']->id)
                ->groupBy('user_id')
                ->orderBy('max_total_thesis', 'desc')
                ->limit(53)->get();
        });

        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    /**
     * Accept support for all the users who have supported but whose leader has not yet accepted the support after the modify timer has passed.
     */
    public function acceptSupportForAll()
    {
        Log::channel('newWeek')->info("Start Check Support Marks");

        //get the week before the current week
        $week = Week::latest()->skip(1)->first();


        //get the support post
        $supportPostTypeId = PostType::where('type', 'support')->first()->id;
        $supportPost = Post::where('type_id', $supportPostTypeId)->latest()->first();

        //get all the users who commented on the support post or reacted to it or voted on the poll
        $reactedUsers = $supportPost->reactions->pluck('user_id')->toArray();
        $votedUsers = $supportPost->pollVotes->pluck('user_id')->toArray();
        $commentedUsers = $supportPost->comments->pluck('user_id')->toArray();

        $users = array_unique(array_merge($reactedUsers, $votedUsers, $commentedUsers));

        //get marks for all the users that didn't have support mark, and update them
        $marksWithoutSupport = Mark::where('support', 0)
            ->where('reading_mark', '!=', 0)
            ->where('week_id', $week->id)
            ->whereIn('user_id', $users)
            ->with('user')
            ->get();

        try {
            DB::beginTransaction();

            //update marks for all the users that didn't have support mark
            Mark::whereIn('id', $marksWithoutSupport->pluck('id'))
                ->update(['support' => 10]);

            DB::commit();

            //notify supervisors that the leader didn't accept the support for some users
            $notification = new NotificationController();
            foreach ($marksWithoutSupport as $mark) {
                $user = $mark->user;
                $leaderRole = $user->parent->roles->first()->name;
                $message = "لم يقبل ال" . config('constants.ARABIC_ROLES')[$leaderRole] . ": " . $user->parent->name . " الدعم للمستخدم: " . $user->name . " في الأسبوع: " . $week->title . ", لذلك تم اعتماد الدعم له تلقائيا";
                $notification->sendNotification($user->parent->parent->id, $message, SUPPORT_MARK);
            }
            Log::channel('newWeek')->info("تم اعتماد الدعم للجميع");
            //return $this->jsonResponseWithoutMessage("تم اعتماد الدعم للجميع", 'data', 200);
        } catch (\Exception $e) {
            DB::rollback();
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }
}
