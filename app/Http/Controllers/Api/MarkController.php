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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\MarkResource;
use App\Models\User;
use App\Models\Week;
use App\Events\MarkStats;
use App\Models\AuditType;
use App\Models\MarksForAudit;
use App\Models\Thesis;
use App\Models\UserException;
use Carbon\Carbon;

class MarkController extends Controller
{
    use ResponseJson;

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

        if ($filter == 'current') {
            $currentMonth = date('m');
        }
        if ($filter == 'previous') {
            $currentMonth = date('m') - 1;
        }

        $weeksInMonth = Week::whereRaw('MONTH(created_at) = ?', [$currentMonth])->get();
        $month_achievement = Mark::where('user_id', $user_id)
            ->whereIn('week_id', $weeksInMonth->pluck('id'))
            ->select(DB::raw('avg(reading_mark + writing_mark + support) as out_of_100 , week_id'))
            ->groupBy('week_id')->get();

        $response['month_achievement'] =  $month_achievement->pluck('out_of_100', 'week.title');
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

        if ($filter == 'current') {
            $week = Week::latest()->pluck('id')->toArray();
            $response['week_mark'] = Mark::whereIn('week_id', $week)->where('user_id', $user_id)->first();
        }
        if ($filter == 'previous') {
            $week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->pluck('id')->toArray();
            $response['week_mark'] = Mark::whereIn('week_id', $week)->where('user_id', $user_id)->first();
        }
        if ($filter == 'in_a_month') {
            $currentMonth = date('m');
            $week = Week::whereRaw('MONTH(created_at) = ?', [$currentMonth])->pluck('id')->toArray();
            $response['week_mark'] = Mark::whereIn('week_id', $week)->where('user_id', $user_id)
                ->select(DB::raw('sum(total_thesis) as total_thesis , sum(total_screenshot) as total_screenshot'))
                ->first();
        }
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    /**
     * get user mark with theses => list only for group administrators
     * 
     * @param  $user_id
     * @return mark;
     */
    public function ambassadorMark($user_id)
    {
        $group_id = UserGroup::where('user_id', $user_id)->where('user_type', 'ambassador')->pluck('group_id')->first();
        if ($group_id) {
            $response['group'] = Group::where('id', $group_id)->with('groupAdministrators')->first();
            if (in_array(Auth::id(), $response['group']->groupAdministrators->pluck('id')->toArray())) {

                $response['currentWeek'] = Week::latest()->first();
                $response['mark'] = Mark::where('user_id', $user_id)->where('week_id', $response['currentWeek']->id)->first();
                $response['theses'] = Thesis::with('book')->where('mark_id',  $response['mark']->id)->get();
                return $this->jsonResponseWithoutMessage($response, 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            return $this->jsonResponseWithoutMessage('ليس سفيرا في اية مجموعة', 'data', 404);
        }
    }
 
}
