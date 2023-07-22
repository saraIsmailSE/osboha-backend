<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Models\Mark;
use App\Models\User;
use App\Models\Week;
use Illuminate\Support\Facades\DB;

/**
 * Description: StatisticsController for Osboha general statistics.
 *
 * Methods: 
 * - byWeek
 */

class StatisticsController extends Controller
{

    use ResponseJson;

    /**
     * Get Statistics By Week ID.
     * 
     * @return statistics;
     */

    public function byWeek($week_id = 0)
    {
        // not specified => get previous week
        if ($week_id == 0) {
            $week = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->first();
        } else {
            $week = Week::latest()->pluck('id')->first();
        }
        $response['week'] = $week;

        $response['total_statistics'] = Mark::without('user', 'week')->where('week_id', $response['week']->id)
            ->where('is_freezed', 0)
            ->select(
                DB::raw('avg(reading_mark + writing_mark + support) as total_avg'),
                DB::raw('sum(total_pages) as total_pages'),
                DB::raw('sum(total_thesis) as total_thesis'),
                DB::raw('sum(total_screenshot) as total_screenshot'),
            )->first();
        $total_100 = Mark::without('user', 'week')->where('week_id', $response['week']->id)
            ->where('is_freezed', 0)
            ->select(
                DB::raw('sum(reading_mark + writing_mark + support) as total_100'),
            )->groupBy('user_id')->get();

        $response['total_100'] = $total_100->where('total_100', 100)->count();

        $total_0 = Mark::without('user', 'week')->where('week_id', $response['week']->id)
            ->where('is_freezed', 0)
            ->select(
                DB::raw('sum(reading_mark + writing_mark + support) as total_0'),
            )->groupBy('user_id')->get();

        $response['total_0'] = $total_100->where('total_0', 0)->count();


        $response['most_read'] = Mark::without('user', 'week')->where('week_id', $response['week']->id)
            ->where('is_freezed', 0)
            ->select('user_id', DB::raw('max(total_pages) as max_total_pages'))
            ->groupBy('user_id')
            ->orderBy('max_total_pages', 'desc')
            ->first();

        $response['freezed'] = Mark::without('user', 'week')->where('week_id', $response['week']->id)
            ->where('is_freezed', 1)
            ->count();
        $response['total_users'] = User::where('is_excluded', 0)->count();
        $response['is_excluded'] = User::where('is_excluded', 1)
            ->whereBetween('updated_at', [$response['week']->created_at, $response['week']->created_at->addDays(7)])->count();
        $response['is_new'] = User::whereBetween('created_at', [$response['week']->created_at, $response['week']->created_at->addDays(7)])->get()->count();
        $response['SQL'] = DB::table('User')->whereBetween('created_at', [$response['week']->created_at, $response['week']->created_at->addDays(7)])->toSql();

        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }
}
