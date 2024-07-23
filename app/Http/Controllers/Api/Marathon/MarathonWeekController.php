<?php

namespace App\Http\Controllers\Api\Marathon;
use App\Http\Controllers\Controller;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\NotFound;
use App\Models\OsbohaMarthon;
use App\Models\MarathonWeek;
use App\Models\MarthonBonus;
use App\Models\Mark;
use App\Models\Thesis;
use App\Models\Week;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\MarathonWeeksResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;




class MarathonWeekController extends Controller
{
    use ResponseJson;

    public function set_weeks(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'weeks_id'     => 'required|array',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        // if (Auth::user()->can('edit week')){
        foreach ($request->weeks_id as $week_id) {
            $marathon_week = MarathonWeek::updateOrCreate(
                ['week_id' => $week_id],
            );
        }
        //  }
        // } else {
        //     throw new NotAuthorized;
        // }

    }
    public function  listMarathonWeeks()
    {
        //  if (Auth::user()->can('edit week') ) {
        $marathon_week = MarathonWeek::where('is_active', 1)->get();
        if ($marathon_week) {
            return $this->jsonResponseWithoutMessage(MarathonWeeksResource::collection($marathon_week), 'data', 200);
        } else {
            throw new NotFound;
        }
        // } else {
        //     throw new NotAuthorized;
        // }

    }
    public function calculateMarkMarathon(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'user_id'             => 'required',
            'osboha_marthon_id'   => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $user_id = $request->user_id;
        $osboha_marthon_id = $request->osboha_marthon_id;


        $weeks_key = MarathonWeek::where('osboha_marthon_id' , $osboha_marthon_id)
                                  ->pluck('week_key');
        // get all weeks of marathon in ascending order
        $weeks_marathon = Week::whereIn('week_key', $weeks_key)
            ->orderBy('created_at', 'ASC')
            ->get();
        if(isset($weeks_marathon[0])){
            $response['point_first_week'] = $this->calculatePoint($weeks_marathon[0], $user_id, $maximum_total_pages = 14);
        }
        if(isset($weeks_marathon[1])){
            $response['point_second_week'] = $this->calculatePoint($weeks_marathon[1], $user_id, $maximum_total_pages = 29);
        }
        if(isset($weeks_marathon[2])){
            $response['point_third_week'] = $this->calculatePoint($weeks_marathon[2], $user_id, $maximum_total_pages = 39);
        }
        if(isset($weeks_marathon[3])){
            $response['point_fourth_week'] = $this->calculatePoint($weeks_marathon[3], $user_id, $maximum_total_pages = 49);
        }
        return $response;
    }


    public function calculatePoint($week_marathon, $user_id, $maximum_total_pages)
    {
        $days  = [];
        $points = 0;
        $i = 0;
       $day = new Carbon($week_marathon->created_at);
        for ($i = 0; $i < 7; $i++) {
            //get  the dates of the seven days in week one.
            $days[] = $day->copy()->addDays($i)->format('Y-m-d');
        }
        // return  $days;
        $mark = Mark::where('user_id', $user_id)
            ->where('week_id', $week_marathon->id)
            ->first();
        if ($mark) {
            $theses = Thesis::where('mark_id', $mark->id)
                ->whereIn(DB::raw('DATE(created_at)'), $days)
                ->where('max_length', '!=', 0)
                ->where('total_screenshots', '==', 0)
                ->select(DB::raw('SUM(end_page - start_page + 1) as total_pages'))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->get();
            foreach ($theses  as  $thises) {
                if ($thises->total_pages > $maximum_total_pages) {
                    $points += 10;
                    $i++; //  الانجاز يكون لخمس ايام فقط لذا يتم زيادة هذا المتغيير في كل مرة يتحقق فيها الشرط حتى اذا اصبحت قيمة هذا المتغير تساوي خمس يتم عمل بريك للفور
                    if ($i == 5) {
                        break;
                    }
                }
            }
        }
        return $points;
    }
    function create_marthon(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'title'                    => 'required_without:osboha_marthon_id',
            'osboha_marthon_id'        => 'required_without:title',
            'weeks_key'                 => 'required|array',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if ($request->has('title')) {
            $osboha_marthon = OsbohaMarthon::updateOrCreate(['title' => $request->title]);
            if ($osboha_marthon) {
                $osboha_marthon_id = $osboha_marthon->id;
            }
        } else if ($request->has('osboha_marthon_id')) {
            $osboha_marthon_id = $request->osboha_marthon_id;
        }
        if ($osboha_marthon_id) {
            foreach ($request->weeks_key as $week_key) {
                MarathonWeek::updateOrCreate(
                    [
                        'osboha_marthon_id' => $osboha_marthon_id,
                        'week_key' => $week_key
                    ],
                );
            }
        }
        return $this->jsonResponseWithoutMessage("Add Marathons Week successfully", 'data', 200);
    }
    function add_bonus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id'           => 'required',
            'osboha_marthon_id' => 'required',
            'option'            => 'required|string',
            'input'             => 'nullable|string',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $points_bonus =  MarthonBonus::updateOrCreate([
            'user_id'           => $request->user_id,
            'osboha_marthon_id' => $request->osboha_marthon_id,

        ]);
        $options = $request->option;
        switch ($options) {
            case 'activity':
                $points_bonus->activity = $request->input * 3;
                break;
            case 'leading_course':
                $points_bonus->leading_course = 5;
                break;
            case 'eligible_book':
                $points_bonus->eligible_book = $request->input * 10;
                break;
            case 'eligible_book_less_VG':
                $points_bonus->eligible_book_less_VG = $request->input * 6;
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
        $response['osboha_marthon_id']           = $points_bonus->osboha_marthon_id;
        $response['activity_bonus']              = $points_bonus->activity;
        $response['leading_course_bonus']        = $points_bonus->leading_course;
        $response['eligible_book_bonus']         = $points_bonus->eligible_book;
        $response['eligible_book_less_VG_bonus'] = $points_bonus->eligible_book_less_VG;


        return $response;
    }
}
