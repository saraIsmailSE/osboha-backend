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
use App\Models\User;
use App\Models\Week;
use App\Events\MarkStats;
use App\Models\AuditNotes;
use App\Models\AuditType;
use App\Models\MarksForAudit;
use App\Models\Thesis;
use App\Models\UserException;
use App\Models\GroupType;
use Carbon\Carbon;
use Throwable;
use App\Traits\GroupTrait;
use App\Traits\PathTrait;
use Illuminate\Support\Facades\Log;

class AuditMarkController extends Controller
{
    use ResponseJson, GroupTrait, PathTrait;


    /**
     * Generate audit marks for supervisors and advisors.
     * automatically on Sunday at 10:00 P.M Saudi Arabia time.

     * This function generates audit marks for supervisors and advisors based on the previous week's data if it is not a vacation.
     * Audit marks are created for follow-up groups only. For supervisors, marks are generated based on
     * full and variant audits, while for advisors, marks are generated based on supervisor audits and
     * non-supervisor audits.
     *
     * @return string|null Returns an error message if an exception occurs, otherwise null.
     */

    public function generateAuditMarks()
    {

        DB::beginTransaction();
        try {
            $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();
            if ($previous_week && !$previous_week->is_vacation) {
                $previous_week->audit_timer = Carbon::now()->addDays(2);
                $previous_week->save();

                //Audit is ONLY for followup groups
                $groupsID = Group::whereHas('type', function ($q) {
                    $q->where('type', '=', 'followup');
                })->pluck('id');

                //Audit type [full - variant - of_supervisor_audit - not_of_supervisor_audit] ]
                $fullAudit = AuditType::where('name', 'full')->first();
                $variantAudit = AuditType::where('name', 'variant')->first();
                $ofSupervisorAudit = AuditType::where('name', 'of_supervisor_audit')->first();
                $notOfSupervisorAudit = AuditType::where('name', 'not_of_supervisor_audit')->first();


                #### Start Supervisor Audit ####

                foreach ($groupsID as $key => $groupID) {
                    $weekAuditMarks = AuditMark::where('week_id', $previous_week->id)->where('group_id', $groupID)->exists();

                    if (!$weekAuditMarks) {
                        // the followup group with its ambassadors
                        $group = Group::where('id', $groupID)->with('userAmbassador')->with('groupSupervisor')->first();

                        if (!$group->groupSupervisor->pluck('id')->first()) {
                            continue;
                        }
                        /**
                         *
                         * for each followup group 20% of marks:
                         * 10% of full marks
                         * 10% not full marks
                         */

                        // All Full Mark
                        $fullMark = Mark::whereIn('user_id', $group->userAmbassador->pluck('id'))
                            ->select(DB::raw('id,(reading_mark + writing_mark + support) as out_of_100'))
                            ->having('out_of_100', 100)
                            ->where('week_id', $previous_week->id)
                            ->count();
                        // 10% of Full Mark
                        $ratioFullMarkToAudit = round($fullMark * 0.10) + 1;
                        $fullMarkToAudit = Mark::whereIn('user_id', $group->userAmbassador->pluck('id'))
                            ->select(DB::raw('id,(reading_mark + writing_mark + support) as out_of_100'))
                            ->having('out_of_100', 100)
                            ->inRandomOrder()
                            ->where('week_id', $previous_week->id)
                            ->limit($ratioFullMarkToAudit)
                            ->pluck('id')->toArray();

                        //NOT Full Mark
                        $lowMark = Mark::whereIn('user_id', $group->userAmbassador->pluck('id'))
                            ->select(DB::raw('id,(reading_mark + writing_mark + support) as out_of_100'))
                            ->having('out_of_100', '<', 100)
                            ->where('week_id', $previous_week->id)
                            ->count();
                        //Get 10% of NOT Full Mark
                        $ratioVariantMarkToAudit = ($lowMark * 0.10) + 1;
                        $variantMarkToAudit = Mark::whereIn('user_id', $group->userAmbassador->pluck('id'))
                            ->select(DB::raw('id,(reading_mark + writing_mark + support) as out_of_100'))
                            ->having('out_of_100', '<', 100)
                            ->inRandomOrder()
                            ->limit($ratioVariantMarkToAudit)
                            ->where('week_id', $previous_week->id)
                            ->pluck('id')->toArray();


                        // create audit_marks record for supervisor [ week_id, auditor_id,	group_id]

                        $supervisorAuditMarks = new AuditMark;
                        $supervisorAuditMarks->week_id = $previous_week->id;
                        $supervisorAuditMarks->auditor_id = $group->groupSupervisor->pluck('id')->first();
                        $supervisorAuditMarks->group_id = $group->id;
                        $supervisorAuditMarks->save();
                        //create marks_for_audits record/s [audit_marks_id	mark_id	type_id	[type could be full - variant] ]

                        // 1- Full Mark
                        foreach ($fullMarkToAudit as $mark) {
                            MarksForAudit::create([
                                'audit_marks_id' => $supervisorAuditMarks->id,
                                'mark_id' => $mark,
                                'type_id' => $fullAudit->id,
                            ]);
                        }


                        // 1- Variant Mark
                        foreach ($variantMarkToAudit as $mark) {
                            MarksForAudit::create([
                                'audit_marks_id' => $supervisorAuditMarks->id,
                                'mark_id' => $mark,
                                'type_id' => $variantAudit->id,
                            ]);
                        }
                    }
                }

                #### END Supervisor Audit ####

                #### Start Advisor Audit ####
                //get all advisors
                $advisors = User::with("roles")->whereHas("roles", function ($q) {
                    $q->where("name", "advisor");
                })->pluck('id');

                // get all groups for each advisor
                foreach ($advisors as $key => $advisor) {
                    // create audit_marks record for advisor for this supervisor [ week_id, auditor_id,	group_id]

                    $advisorAuditMarks = new AuditMark;
                    $advisorAuditMarks->week_id = $previous_week->id;
                    $advisorAuditMarks->auditor_id = $advisor;
                    $advisorAuditMarks->save();

                    // get all groups
                    $groupsID = UserGroup::where('user_id', $advisor)->where('user_type', 'advisor')->whereNull('termination_reason')
                        ->whereHas('group.type', function ($q) {
                            $q->where('type', '=', 'followup');
                        })->pluck('group_id');
                    // get supervisors of $advisor [Based on where the advisor is advisor]
                    $supervisors = UserGroup::where('user_type', 'supervisor')->whereNull('termination_reason')->whereIn('group_id', $groupsID)->distinct()->get(['user_id']);

                    // get Audit [in the current week] for each $supervisor
                    foreach ($supervisors as $key => $supervisor) {
                        // get audit marks [in the current week] for each $supervisor
                        $auditMarks = AuditMark::where('auditor_id', $supervisor->user_id)->where('week_id', $previous_week->id)->get()->pluck('id');
                        // get count of marks of supervisor audit
                        $supervisorAudit = MarksForAudit::whereIn('audit_marks_id', $auditMarks)->get()->pluck('mark_id');

                        // 5% supervisorAuditCount[updated 01-23-2024]
                        $ratioToAudit = round(count($supervisorAudit) * 0.05) + 1;
                        $marksOfSupervisorAudit = Mark::whereIn('id', $supervisorAudit)
                            ->inRandomOrder()
                            ->limit($ratioToAudit)
                            ->pluck('id')->toArray();

                        // 5% of OTHER Marks
                        /* get all related Ambassadors
                   * 1- get all supervisor groups
                   * 2- get ambassadors
                   */
                        $supervisorsGroups = UserGroup::where('user_id', $supervisor->user_id)->where('user_type', 'supervisor')->whereNull('termination_reason')->pluck('group_id');
                        $ambassadors = UserGroup::where('user_type', 'ambassador')->whereIn('group_id', $supervisorsGroups)->whereNull('termination_reason')->distinct()->pluck('user_id');
                        // get 1% of ther marks that NOT in supervisorAudit [updated 01-23-2024]
                        $ratioToAudit = round(count($ambassadors) * 0.01) + 1;
                        $marksOfNotSupervisorAudit = Mark::whereIn('user_id', $ambassadors)->whereNotIn('id', $supervisorAudit)
                            ->where('week_id', $previous_week->id)
                            ->inRandomOrder()
                            ->limit($ratioToAudit)
                            ->pluck('id')->toArray();

                        //1- ofSupervisorAudit
                        foreach ($marksOfSupervisorAudit as $mark) {
                            MarksForAudit::create([
                                'audit_marks_id' => $advisorAuditMarks->id,
                                'mark_id' => $mark,
                                'type_id' => $ofSupervisorAudit->id,
                            ]);
                        }
                        // 1- NotSupervisorAudit
                        foreach ($marksOfNotSupervisorAudit as $mark) {
                            MarksForAudit::create([
                                'audit_marks_id' => $advisorAuditMarks->id,
                                'mark_id' => $mark,
                                'type_id' => $notOfSupervisorAudit->id,
                            ]);
                        }
                    }
                }

                #### End Advisor Audit ####
                Log::channel('auditMarks')->info("generated successfully");
            } else {
                Log::channel('auditMarks')->info("no Marks for audit [vacation]");
            }
            DB::commit();
        } catch (\Exception $e) {
            Log::channel('auditMarks')->info($e);
            DB::rollBack();

            return $e->getMessage();
        }
    }


    /**
     * get audit marks with group exceptions => list only for group administrators
     *  @param  group_id
     * @return mark with Achievement
     */
    public function groupAuditMarks($group_id)
    {
        $response['group'] = Group::with('leaderAndAmbassadors')->find($group_id);
        $response['week'] =  Week::where('is_vacation', 0)->orderBy('created_at', 'desc')->first();

        $response['audit_mark'] = AuditMark::where('week_id', $response['week']->id)->where('group_id', $group_id)->first();
        //Audit Marks by type [full - variant]
        if ($response['audit_mark']) {
            $response['fullAudit'] = MarksForAudit::whereHas('type', function ($q) {
                $q->where('name', '=', 'full');
            })->where('audit_marks_id',  $response['audit_mark']->id)->get();
            $response['variantAudit'] = MarksForAudit::whereHas('type', function ($q) {
                $q->where('name', '=', 'variant');
            })->where('audit_marks_id',  $response['audit_mark']->id)->get();
        }
        $response['exceptions'] = UserException::with('User')->whereIn('user_id', $response['group']->leaderAndAmbassadors->pluck('id'))->where('week_id', $response['week']->id)->latest()->get();

        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    /**
     * Find an existing  mark for audit by audit record id  ( “audit mark” permission is required).
     *
     * @param  $mark_id
     * @return jsonResponseWithoutMessage;
     */
    public function markForAudit($mark_for_audit_id)
    {
        if (Auth::user()->can('audit mark')) {
            $response['mark_for_audit'] = MarksForAudit::with('auditNotes')->find($mark_for_audit_id);
            $response['week'] = Week::where('id', $response['mark_for_audit']->auditMark->week_id)->first();
            $group_id = UserGroup::where('user_id', $response['mark_for_audit']->mark->user_id)->where('user_type', 'ambassador')->whereNull('termination_reason')->pluck('group_id')->first();
            $response['group'] = Group::where('id', $group_id)->with('groupAdministrators')->first();
            $response['authorized'] = Auth::user()->hasanyrole('admin|consultant|supervisor|advisor');
            $response['theses'] = Thesis::with('book')->where('mark_id',  $response['mark_for_audit']->mark_id)->get();
            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * Update Mark for Audit Status.
     *
     * @param  Request  $request, mark_for_audit_id
     * @return jsonResponseWithoutMessage;
     */
    public function updateMarkForAuditStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->can('audit mark')) {
            $mark_for_audit = MarksForAudit::where('id', $id)
                ->update(['status' => $request->status]);
            if ($mark_for_audit) {
                return $this->jsonResponseWithoutMessage("Updated Successfully", 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * get all groups audit for supervisor.
     *
     * @return jsonResponseWithoutMessage;
     */

    public function groupsAudit($supervisor_id)
    {
        if (Auth::user()->hasanyrole('admin|supervisor|advisor')) {
            // get all groups for a supervisor
            $groupsID = UserGroup::where('user_id', $supervisor_id)->where('user_type', 'supervisor')->whereNull('termination_reason')->pluck('group_id');
            $response['groups'] = Group::withCount('leaderAndAmbassadors')->whereHas('type', function ($q) {
                $q->where('type', '=', 'followup');
            })->whereIn('id', $groupsID)->without('Timeline')->get();
            $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->pluck('id')->first();


            foreach ($response['groups']  as $key => $group) {
                $users_in_group = $this->usersByWeek($group->id, $previous_week, ['leader', 'ambassador']);
                $week_avg = $this->groupAvg($group->id,  $previous_week, $users_in_group->pluck('user_id'));
                //add marks_week_avg to group object
                $group->setAttribute('marks_week_avg', $week_avg);
            }

            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * get all supervisors audit for Advisor.
     *
     * @return jsonResponseWithoutMessage;
     */


    public function allSupervisorsForAdvisor($advisor_id)
    {

        if (Auth::user()->hasanyrole('admin|advisor')) {
            $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->pluck('id')->first();
            // get all groups ID for this advisor
            $groupsID = UserGroup::where('user_id', $advisor_id)->where('user_type', 'advisor')->whereNull('termination_reason')
                ->whereHas('group.type', function ($q) {
                    $q->where('type', '=', 'followup');
                })->pluck('group_id');
            // all supervisors of advisor (unique)
            $supervisors = UserGroup::with('group')->where('user_type', 'supervisor')->whereIn('group_id', $groupsID)->get()->unique('user_id');
            $response = [];
            foreach ($supervisors as $key => $supervisor) { //for each supervisor of advisor
                // supervisor name
                $supervisorinfo['supervisor'] = $supervisor->group->groupSupervisor->first();
                //all group for $supervisor
                $groups = UserGroup::with('group')->where('user_type', 'supervisor')->where('user_id', $supervisor->user_id)->whereNull('termination_reason')->get(['group_id']);
                // num of leaders
                $supervisorinfo['num_of_leaders'] = $groups->count();
                // marks week_avg for each group
                $total_avg = 0;
                foreach ($groups as $group) {
                    $users_in_group = $this->usersByWeek($group->group_id, $previous_week, ['leader', 'ambassador']);
                    $week_avg = $this->groupAvg($group->group_id,  $previous_week, $users_in_group->pluck('user_id'));
                    $total_avg += $week_avg;
                }
                // marks week avg for all $supervisor groups
                $supervisorinfo['groups_avg'] = $total_avg / $supervisorinfo['num_of_leaders'];
                $auditMarksRecoreds = AuditMark::where('auditor_id', $supervisor->user_id)->where('week_id', $previous_week)->pluck('id');
                if ($auditMarksRecoreds) {
                    $supervisorinfo['audit_count'] = MarksForAudit::whereIn('audit_marks_id', $auditMarksRecoreds)->count();
                    $supervisorinfo['audit_audited'] = MarksForAudit::where('status', '!=', 'not_audited')->whereIn('audit_marks_id', $auditMarksRecoreds)->count();
                } else {
                    $supervisorinfo['audit_count'] = 0;
                    $supervisorinfo['audit_audited'] = 0;
                }
                $response[$key] = $supervisorinfo;
            }
            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }


    public function advisorMainAudit($advisor_id)
    {

        if (Auth::user()->hasanyrole('admin|advisor')) {
            // $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->pluck('id')->first();
            $previous_week =  Week::where('is_vacation', 0)->orderBy('created_at', 'desc')->first();
            // get advisor audit
            $advisorAudit = AuditMark::where('auditor_id', $advisor_id)->whereNull('group_id')->where('week_id', $previous_week)->first();

            $response = [];
            foreach ($advisorAudit->marksForAudit as $key => $mark) { //for each audit of advisor
                $auditInfo['audit_mark'] = $mark;
                // get userinfo from mark
                $userMark = Mark::find($mark->mark_id);

                //group where user is ambassador
                $ambassador_group_id = UserGroup::where('user_type', 'ambassador')->whereNull('termination_reason')->where('user_id', $userMark->user_id)->first();
                if ($ambassador_group_id) {
                    $supervisorID = UserGroup::where('user_type', 'supervisor')->whereNull('termination_reason')->where('group_id', $ambassador_group_id->group_id)->first();
                    if ($supervisorID) {
                        $supervisor = User::find($supervisorID->user_id);
                        $auditInfo['supervisor_name'] = $supervisor->name . " " . $supervisor->last_name;
                        $response[$key] = $auditInfo;
                    }
                }
                // get supervisor of the group
            }
            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * Add audit note.
     *
     * @return created note;
     */

    public function addNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'body' => 'required',
            'mark_for_audit_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        try {
            $note = AuditNotes::create([
                'mark_for_audit_id' => $request->mark_for_audit_id,
                'from_id' => Auth::id(),
                'body' => $request->body
            ]);
            $mark = Mark::find($request->mark_for_audit_id);

            $userGroup = UserGroup::where('user_id', $mark->user->id)->where('user_type', 'ambassador')->whereNull('termination_reason')->first();
            $supportLeaderInGroup = UserGroup::where('group_id', $userGroup->group_id)
                ->where('user_type', 'support_leader')->whereNull('termination_reason')
                ->first();

            $parentId = $supportLeaderInGroup ? $supportLeaderInGroup->user_id : $mark->user->parent_id;

            $msg = "لقد أرسل إليك " . Auth::user()->name . " ملاحظة حول علامة";
            (new NotificationController)->sendNotification($parentId, $msg, AUDIT_MARKS, $this->getAuditMarkPath($request->mark_for_audit_id));

            return $this->jsonResponseWithoutMessage($note, 'data', 200);
        } catch (Throwable $e) {
            report($e);

            return $e;
        }
    }

    /**
     * Add audit note.
     *
     * @return created note;
     */

    public function getNotes($mark_for_audit_id)
    {
        try {
            $notes = AuditNotes::where('mark_for_audit_id', $mark_for_audit_id)->get();

            return $this->jsonResponseWithoutMessage($notes, 'data', 200);
        } catch (Throwable $e) {
            report($e);
            return false;
        }
    }

    public function pendingTheses($supervisor_id, $week_id = 0)
    {

        if (Auth::user()->hasanyrole('admin|advisor|supervisor')) {
            if ($week_id == 0) {
                $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(10)->first();
                $week_id = $previous_week->id;
            }

            $response = [];

            //supervising group and leaders for this supervisor
            $group = Group::whereHas('type', function ($q) {
                $q->where('type', 'supervising');
            })
                ->whereHas('users', function ($q) use ($supervisor_id) {
                    $q->where('user_id', $supervisor_id);
                })
                ->with('userAmbassador:id,name') //***/
                ->first();


            if ($group) {

                $ambassadors = $group->userAmbassador->mapWithKeys(function ($leader) use ($week_id) {
                    // Fetch information about each leader and their associated group
                    $ambassador = UserGroup::where('user_id', $leader->id)
                        ->where('user_type', 'leader')->whereNull('termination_reason')
                        ->with(['group' => function ($query) use ($week_id) {
                            $query->select('id')->with(['userAmbassador.theses' => function ($query) use ($week_id) {
                                // Filter the theses to include only pending ones
                                $query->where('status', 'pending')
                                    ->whereHas('mark', function ($q) use ($week_id) {
                                        $q->where('week_id', $week_id);
                                    });
                            }]);
                        }])
                        ->first();

                    // Check if $ambassador is not null and pending_theses is not null before including it
                    if ($ambassador && $ambassador->group->userAmbassador->pluck('theses')->flatten()->isNotEmpty()) {
                        $ambassadorsData = $ambassador->group->userAmbassador
                            ->filter(function ($ambassador) {
                                return $ambassador->theses->isNotEmpty();
                            })
                            ->mapWithKeys(function ($ambassador) {
                                return [$ambassador->name => [
                                    'pending_theses' => $ambassador->theses,
                                ]];
                            })
                            ->all();

                        return [$leader->name => $ambassadorsData];
                    }

                    // If $ambassador is null or pending_theses is null or empty, skip this iteration
                    return [];
                });
                return $this->jsonResponseWithoutMessage($ambassadors, 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
}
