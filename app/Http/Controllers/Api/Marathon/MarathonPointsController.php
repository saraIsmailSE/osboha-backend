<?php

namespace App\Http\Controllers\Api\Marathon;

use App\Exceptions\NotAuthorized;
use App\Http\Controllers\Controller;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\NotFound;
use App\Models\OsbohaMarthon;
use App\Models\MarathonWeek;
use App\Models\MarthonBonus;
use App\Models\Mark;
use App\Models\Thesis;
use App\Models\UserGroup;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class MarathonPointsController extends Controller
{
    use ResponseJson;

    public function calculatePoint($week, $user_id, $maximum_total_pages)
    {
        $points = 0;
        $day = new Carbon($week->created_at);
        $weekPlusSevenDays = $week->created_at->addDays(7);


        // Retrieve the Mark record
        $mark = Mark::where('user_id', $user_id)
            ->where('week_id', $week->id)
            ->first();

        $daily_totals = [];
        $points = 0;


        if ($mark) {
            $theses = Thesis::where('mark_id', $mark->id)
                ->whereBetween('created_at', [$week->created_at, $weekPlusSevenDays])
                ->select(DB::raw('DATE(created_at) as date, SUM(end_page - start_page + 1) as total_pages, SUM(max_length) as theses_length, COUNT(*) as total_theses'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get();

            // Calculate points based on the total pages
            foreach ($theses as $thesis) {
                $date = $thesis->date;
                $dayName = Carbon::parse($date)->format('l'); // Get the day name
                $daily_points = 0;
                if ($thesis->total_pages >= $maximum_total_pages) {
                    $points += 5;
                    $daily_points += 5;
                    if ($thesis->theses_length > 0) {
                        $points += 5;
                        $daily_points += 5;
                    }
                }
                $daily_totals[] = [
                    'date' => $date,
                    'day' => $dayName,
                    'total_pages' => $thesis->total_pages,
                    'total_theses' => $thesis->total_theses,
                    'daily_points' => $daily_points,
                ];
                // Stop if points = 50
                if ($points >= 50) {
                    break;
                }
            }
        }

        return [
            'points' => $points,
            'daily_totals' => $daily_totals,
        ];
    }


    public function getMarathonPoints($user_id, $osboha_marthon_id)
    {

        $osboha_marathon = OsbohaMarthon::find($osboha_marthon_id);

        if ($osboha_marathon) {

            $user_group = UserGroup::where('user_id', $user_id)->where('user_type', 'marathon_ambassador')->whereNull('termination_reason')->first();
            $marathonYear = $osboha_marathon->created_at->year;

            $response['osboha_marathon'] = $osboha_marathon;
            $response['group_name'] = $user_group->group->name;
            $response['user_name'] = $user_group->user->name . " " . $user_group->user->last_name;

            $weeks_key = MarathonWeek::where('osboha_marthon_id', $osboha_marathon->id)
                ->pluck('week_key');

            $marathon_weeks = Week::whereIn('week_key', $weeks_key)
                ->whereYear('created_at', $marathonYear) // Filter by the same year
                ->get();

            $basic_points = [];
            $point_details = [];

            // Maximum total pages for each week
            $maximum_total_pages = [
                15, // First week
                30, // Second week
                40, // Third week
                50  // Fourth week
            ];

            // Initialize response with 0 points for each week
            for ($i = 0; $i < count($maximum_total_pages); $i++) {
                $basic_points["point_week_" . ($i + 1)] = 0;
                $point_details["point_week_" . ($i + 1)] = [];
            }

            Log::channel('auditMarks')->info("test: " . count($maximum_total_pages));

            $week_index = 0;
            $total_basic_points = 0;
            foreach ($marathon_weeks as $week) {
                if ($week_index < count($maximum_total_pages)) {
                    $result = $this->calculatePoint($week, $user_id, $maximum_total_pages[$week_index]);
                    $basic_points["point_week_" . ($week_index + 1)] =  $result['points'];
                    $point_details["point_week_" . ($week_index + 1)] =  $result['daily_totals'];
                    $total_basic_points += $result['points'];
                    $week_index++;
                } else {
                    break; // Exit the loop if there are more weeks than defined maximum_total_pages
                }
            }

            $bonus_points = MarthonBonus::where('user_id', $user_id)
                ->where('osboha_marthon_id',  $osboha_marathon->id)
                ->select(DB::raw('COALESCE(SUM(activity), 0) +
                    COALESCE(SUM(leading_course), 0) +
                    COALESCE(SUM(eligible_book), 0) +
                    COALESCE(SUM(eligible_book_less_VG), 0) AS bonus_points'))
                ->value('bonus_points');

            if (is_null($bonus_points)) {
                $bonus_points = 0;
            }

            $response['point_details'] = $point_details;
            $response['basic_points'] = $basic_points;
            $response['bonus_points'] = $bonus_points;
            $response['total_points'] = $total_basic_points + $bonus_points;

            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        }
        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }
    public function getSpecificMarathonWeekPoints($user_id, $osboha_marthon_id, $week_id)
    {

        $osboha_marathon = OsbohaMarthon::find($osboha_marthon_id);
        $week = Week::find($week_id);
        $points = 0;

        if ($osboha_marathon && $week) {

            $marathon_week = MarathonWeek::where('osboha_marthon_id', $osboha_marathon->id)
                ->where('week_key', $week->week_key)
                ->first();

            if ($marathon_week) {
                // Get all marathon week_keys ordered by week_key
                $marathon_week_keys = MarathonWeek::where('osboha_marthon_id', $osboha_marathon->id)
                    ->orderBy('week_key', 'ASC')
                    ->pluck('week_key')
                    ->toArray();


                // Find the position of the specific week_key in the ordered array
                $order = array_search($marathon_week->week_key, $marathon_week_keys);
                $points = 0;

                if ($order !== false) {
                    $maximum_total_pages = [
                        15, // First week
                        30, // Second week
                        40, // Third week
                        50  // Fourth week
                    ];
                    $total_pages = $maximum_total_pages[$order];
                    $result =  $this->calculatePoint($week, $user_id, $total_pages);

                    $points = $result['points'];
                }
            }
        }

        return $this->jsonResponseWithoutMessage($points, 'data', 200);
    }


    function addBonus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required',
            'osboha_marthon_id' => 'required',
            'bonus_type'            => 'required|string',
            'amount'             => 'required',
            'eligible_book_avg' => 'required_if:bonus_type,eligible_book',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $points_bonus =  MarthonBonus::updateOrCreate([
            'user_id'           => $request->user_id,
            'osboha_marthon_id' => $request->osboha_marthon_id,

        ]);
        if (!Auth::user()->hasanyrole(['admin', 'marathon_coordinator'])) {
            throw new NotAuthorized;
        }

        $bonus_type = $request->bonus_type;
        switch ($bonus_type) {
            case 'activity':
                if ($points_bonus->activity < 6) {
                    if ($request->amount >= 2) {
                        $points_bonus->activity = 6;
                    } else {
                        $points_bonus->activity = $request->amount * 3;
                    }
                }
                break;
            case 'leading_course':
                $points_bonus->leading_course = 5;
                break;
            case 'eligible_book':
                if ($request->eligible_book_avg == 'vg_above') {
                    $points_bonus->eligible_book = $request->amount * 10;
                } else if ($request->eligible_book_avg == 'vg_less') {
                    $points_bonus->eligible_book_less_VG = $request->amount * 6;
                }
                break;
            default:
                return $this->jsonResponse(
                    [],
                    'data',
                    Response::HTTP_BAD_REQUEST,
                    "الرجاء تحديد نوع النشاط"
                );
        }
        $points_bonus->save();

        return $this->jsonResponseWithoutMessage($points_bonus, 'data', 200);
    }
    function getBonusPoints($user_id, $osboha_marthon_id)
    {
        $points_bonus =  MarthonBonus::where('user_id', $user_id)->where('osboha_marthon_id', $osboha_marthon_id)->first();
        return $this->jsonResponseWithoutMessage($points_bonus, 'data', 200);
    }

    function subtractPoints(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required',
            'osboha_marthon_id' => 'required',
            'bonus_type'            => 'required|string',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (!Auth::user()->hasanyrole(['admin', 'marathon_coordinator'])) {
            throw new NotAuthorized;
        }

        $points_bonus =  MarthonBonus::where('user_id', $request->user_id)->where('osboha_marthon_id', $request->osboha_marthon_id)->first();
        if ($points_bonus) {
            $bonus_type = $request->bonus_type;
            switch ($bonus_type) {
                case 'activity':
                    $points_bonus->activity = 0;
                    break;
                case 'leading_course':
                    $points_bonus->leading_course = 0;
                    break;
                case 'eligible_book':
                    $points_bonus->eligible_book = 0;
                    break;
                case 'eligible_book_less_VG':
                    $points_bonus->eligible_book_less_VG = 0;
                    break;
            }
            $points_bonus->save();
        }
        return $this->jsonResponseWithoutMessage($points_bonus, 'data', 200);
    }
    public function getMarathonPointsDetails($user_id, $osboha_marthon_id)
    {

        $osboha_marathon = OsbohaMarthon::find($osboha_marthon_id);

        if ($osboha_marathon) {
            $marathonYear = $osboha_marathon->created_at->year;

            $weeks_key = MarathonWeek::where('osboha_marthon_id', $osboha_marathon->id)
                ->pluck('week_key');

            $marathon_weeks = Week::whereIn('week_key', $weeks_key)
                ->whereYear('created_at', $marathonYear) // Filter by the same year
                ->get();

            $basic_points = [];

            // Maximum total pages for each week
            $maximum_total_pages = [
                14, // First week
                29, // Second week
                39, // Third week
                49  // Fourth week
            ];

            // Initialize response with 0 points for each week
            for ($i = 0; $i < count($maximum_total_pages); $i++) {
                $basic_points["point_week_" . ($i + 1)] = 0;
            }

            $week_index = 0;
            $points = 0;
            foreach ($marathon_weeks as $week) {
                if ($week_index < count($maximum_total_pages)) {
                    $result =  $this->calculatePoint($week, $user_id, $maximum_total_pages[$week_index]);
                    $basic_points["point_week_" . ($week_index + 1)]['points'] = $result['points'];
                    $basic_points["point_week_" . ($week_index + 1)]['daily_totals'] = $result['daily_totals'];
                    $week_index++;
                } else {
                    break; // Exit the loop if there are more weeks than defined maximum_total_pages
                }
            }

            $bonus_points = DB::table('marathon_bonuses')
                ->where('user_id', $user_id)
                ->where('osboha_marthon_id',  $osboha_marathon->id)
                ->select(DB::raw('COALESCE(SUM(activity), 0) +
                    COALESCE(SUM(leading_course), 0) +
                    COALESCE(SUM(eligible_book), 0) +
                    COALESCE(SUM(eligible_book_less_VG), 0) AS bonus_points'))
                ->value('bonus_points');

            if (is_null($bonus_points)) {
                $bonus_points = 0;
            }

            $response['basic_points'] = $basic_points;
            $response['bonus_points'] = $bonus_points;
            $response['total'] = $points + $bonus_points;
            return $this->jsonResponseWithoutMessage($response, 'data', 200);
        }
        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }

    function addMarathonPointsDeductionReasons(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required',
            'osboha_marthon_id' => 'required',
            'week_key'          => 'required',
            'reason'            => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $marathon_point_deduction = MarathonPointDeduction::updateOrCreate(
        [
            'osboha_marthon_id' => $request->osboha_marthon_id, 
            'week_key' => $request->week_key, 
            'user_id' => $request->user_id,
        ],
        [
            'reviewer_id' => Auth::id(),
            'reason' => $request->reason,           
        ]
        ); 
        return $this->jsonResponseWithoutMessage($marathon_point_deduction, 'data', 200);
    }
    function showMarathonPointsDeductionReasons($user_id ,$osboha_marthon_id){
        $deductionPoints = 1;
        $outFromMarathon = false;
        $osboha_marathon = OsbohaMarthon::find($osboha_marthon_id);
        if ($osboha_marathon) {
            $weeks_key = MarathonWeek::where('osboha_marthon_id', $osboha_marathon->id)
                ->pluck('week_key');
             $deduction = MarathonPointDeduction::whereIn('week_key', $weeks_key)
                ->select('week_key',
                    DB::raw("
                        SUM(
                            CASE 
                                WHEN reason = '1' THEN 5
                                WHEN reason = '2' THEN 5
                                WHEN reason = '3' THEN 50
                                WHEN reason = '4' THEN 50
                                ELSE 0
                            END
                        ) as total_deductionPoints,
                        MAX(CASE
                            WHEN reason = '4' THEN 1
                            ELSE 0
                        END) as outFromMarathon
                    "))
                ->groupBy('week_key')
                ->get();
        }
        if($deduction){
            return $this->jsonResponseWithoutMessage($deduction, 'data', 200);
        }
        else{
            throw new NotFound;
        }
             
    }
}
