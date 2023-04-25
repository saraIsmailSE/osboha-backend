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
use App\Models\AuditType;
use App\Models\MarksForAudit;
use App\Models\Thesis;
use App\Models\UserException;
use Carbon\Carbon;

class AuditMarkController extends Controller
{
    use ResponseJson;


    /**
     * Generate audit marks for supervisor and advisor for each followup group in the current week,
     * (if this week is not vacation) automatically on Sunday at 6:00 A.M Saudi Arabia time.
     * 
     * @return jsonResponseWithoutMessage;
     */

    public function generateAuditMarks()
    {
        try {
            $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();
            if ($previous_week) {
                $previous_week->audit_timer = Carbon::now()->addDays(3);
                $previous_week->save();

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
                        $ratioFullMarkToAudit = round($fullMark * 0.10);
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
                        $ratioVariantMarkToAudit = $lowMark * 10 / 100;
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
                    $groupsID = UserGroup::where('user_id', $advisor)->pluck('group_id');
                    // get supervisors of $advisor
                    $supervisors = UserGroup::where('user_type', 'supervisor')->whereIn('group_id', $groupsID)->distinct()->get(['user_id']);

                    // get Audit [in the current week] for each $supervisor
                    foreach ($supervisors as $key => $supervisor) {
                        // get audit marks [in the current week] for each $supervisor
                        $auditMarks = AuditMark::where('auditor_id', $supervisor->user_id)->where('week_id', $previous_week->id)->get()->pluck('id');
                        // get count of marks of supervisor audit 
                        $supervisorAudit = MarksForAudit::whereIn('audit_marks_id', $auditMarks)->get()->pluck('mark_id');

                        // 10% supervisorAuditCount
                        $ratioToAudit = round(count($supervisorAudit) * 0.10);
                        $marksOfSupervisorAudit = Mark::whereIn('id', $supervisorAudit)
                            ->inRandomOrder()
                            ->limit($ratioToAudit)
                            ->pluck('id')->toArray();

                        // 5% of OTHER Marks 
                        /* get all related Ambassadors
                   * 1- get all supervisor groups
                   * 2- get ambassadors
                   */
                        $supervisorsGroups = UserGroup::where('user_id', $supervisor->user_id)->where('user_type', 'supervisor')->pluck('group_id');
                        $ambassadors = UserGroup::where('user_type', 'ambassador')->whereIn('group_id', $supervisorsGroups)->distinct()->pluck('user_id');
                        // get 5% of ther marks that NOT in supervisorAudit
                        $ratioToAudit = round(count($ambassadors) * 0.05);
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

                return $this->jsonResponseWithoutMessage('generated successfully', 'data', 200);
            } else {
                return $this->jsonResponseWithoutMessage('No week', 'data', 200);
            }
        } catch (\Exception $e) {

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
        $response['week'] = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();
        $response['audit_mark'] = AuditMark::where('week_id', $response['week']->id)->where('group_id', $group_id)->first();
        //Audit Marks by type [full - variant]
        $response['fullAudit'] = MarksForAudit::whereHas('type', function ($q) {
            $q->where('name', '=', 'full');
        })->where('audit_marks_id',  $response['audit_mark']->id)->get();
        $response['variantAudit'] = MarksForAudit::whereHas('type', function ($q) {
            $q->where('name', '=', 'variant');
        })->where('audit_marks_id',  $response['audit_mark']->id)->get();

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
            $group_id = UserGroup::where('user_id', $response['mark_for_audit']->mark->user_id)->where('user_type', 'ambassador')->pluck('group_id')->first();
            $response['group'] = Group::where('id', $group_id)->with('groupAdministrators')->first();

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
        if ( !Auth::user()->hasRole('ambassador') || !Auth::user()->hasRole('leader')) {
            // get all groups for auth supervisor
            $groupsID = UserGroup::where('user_id', $supervisor_id)->where('user_type', 'supervisor')->pluck('group_id');
            $response['groups'] = Group::withCount('leaderAndAmbassadors')->whereIn('id', $groupsID)->without('Timeline')->get();
            $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->pluck('id')->first();

            foreach ($response['groups']  as $key => $group) {
                $week_avg = Mark::where('week_id', $previous_week)
                    ->whereIn('user_id', $group->leaderAndAmbassadors->pluck('id'))
                    //avg from (reading_mark + writing_mark + support)
                    ->select(DB::raw('avg(reading_mark + writing_mark + support) as out_of_100'))
                    ->first()
                    ->out_of_100;
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
        throw new NotAuthorized;

        if ( !Auth::user()->hasRole('ambassador') || !Auth::user()->hasRole('leader')) {
            $previous_week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->pluck('id')->first();
            // get all groups ID for this advisor
            $groupsID = UserGroup::where('user_id', $advisor_id)->where('user_type', 'advisor')->pluck('group_id');
            // all supervisors of advisor (unique)
            $supervisors = UserGroup::with('group')->where('user_type', 'supervisor')->whereIn('group_id', $groupsID)->get()->unique('user_id');
            $response=[];
            foreach ($supervisors as $key => $supervisor) { //for each supervisor of advisor 
                // supervisor name
                $supervisorinfo['supervisor'] = $supervisor->group->groupSupervisor->first();
                //all group for $supervisor
                $groups = UserGroup::with('group')->where('user_type', 'supervisor')->where('user_id', $supervisor->user_id)->get(['group_id']);
                // num of leaders
                $supervisorinfo['num_of_leaders'] = $groups->count();
                // marks week_avg for each group
                $total_marks_week = 0;
                foreach ($groups as $group) {
                    $week_avg = Mark::where('week_id', $previous_week)
                        ->whereIn('user_id', $group->group->leaderAndAmbassadors->pluck('id'))
                        //avg from (reading_mark + writing_mark + support)
                        ->select(DB::raw('avg(reading_mark + writing_mark + support) as out_of_100'))
                        ->first()
                        ->out_of_100;
                    $total_marks_week += $week_avg;
                }
                // marks week avg for all $supervisor groups
                $supervisorinfo['groups_avg'] = $total_marks_week / $supervisorinfo['num_of_leaders'];
                $response[$key] = $supervisorinfo;
            }
            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }
}
