<?php

namespace App\Http\Controllers\Api\Ramadan;

use App\Http\Controllers\Controller;
use App\Models\RamadanGolenDay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\DB;

class RamadanGolenDayController extends Controller
{
    use ResponseJson;

    public function store(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'ramadan_day_id' => 'required|exists:ramadan_days,id',
            'sunan_al_rawatib' => 'required|integer',
            'tasbeeh' => 'required|integer',
            'istighfar' => 'required|integer',
            'duha_prayer' => 'required|integer',
            'morning_evening_dhikr' => 'required|integer',
            'shaf_and_witr' => 'required|integer',
            'suhoor' => 'required|integer',
            'drink_water' => 'required|integer',
            'sleep_amount' => 'required|integer',
            'brushing_teeth' => 'required|integer',
            'contemplation_of_allahs_signs' => 'required|integer',
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }



        try {
            $user_id = Auth::id();

            $golden_day_data = [
                'user_id' => $user_id,
                'ramadan_day_id' => $request->ramadan_day_id,
                'sunan_al_rawatib' => $request->sunan_al_rawatib,
                'tasbeeh' => $request->tasbeeh,
                'istighfar' => $request->istighfar,
                'duha_prayer' => $request->duha_prayer,
                'morning_evening_dhikr' => $request->morning_evening_dhikr,
                'shaf_and_witr' => $request->shaf_and_witr,
                'suhoor' => $request->suhoor,
                'drink_water' => $request->drink_water,
                'sleep_amount' => $request->sleep_amount,
                'brushing_teeth' => $request->brushing_teeth,
                'contemplation_of_allahs_signs' => $request->contemplation_of_allahs_signs,
            ];

            $golden_day = RamadanGolenDay::updateOrCreate(
                ['user_id' => $user_id, 'ramadan_day_id' => $request->ramadan_day_id],
                $golden_day_data
            );

            return $this->jsonResponseWithoutMessage($golden_day, 'data', 200);
        } catch (\Exception $e) {
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    public function statistics($ramadan_day_id)
    {
        $statistics = [];

        // 1. Number of distinct users who got points = 30
        $statistics['distinct_users_30'] = RamadanGolenDay::where(DB::raw("sunan_al_rawatib + tasbeeh + istighfar + duha_prayer + morning_evening_dhikr + shaf_and_witr + suhoor + drink_water + sleep_amount + brushing_teeth + contemplation_of_allahs_signs"), 30)
            ->distinct('user_id')
            ->count();


        // 2. Number of distinct users who got points = 30 for 5 days
        $statistics['users_completed_5_golden_days'] = RamadanGolenDay::select('user_id')
            ->groupBy('user_id')
            ->havingRaw("SUM(sunan_al_rawatib + tasbeeh + istighfar + duha_prayer + morning_evening_dhikr + shaf_and_witr + suhoor + drink_water + sleep_amount + brushing_teeth + contemplation_of_allahs_signs) = ?", [30 * 5])
            ->distinct('user_id')
            ->count();

        // 3. Number of distinct users who got points = 30 for 10 days
        $statistics['users_completed_10_golden_days'] = RamadanGolenDay::select('user_id')
            ->groupBy('user_id')
            ->havingRaw("SUM(sunan_al_rawatib + tasbeeh + istighfar + duha_prayer + morning_evening_dhikr + shaf_and_witr + suhoor + drink_water + sleep_amount + brushing_teeth + contemplation_of_allahs_signs) = ?", [30 * 10])
            ->distinct('user_id')
            ->count();

        // 4. Number of distinct users who got points = 30 for 20 days
        $statistics['users_completed_20_golden_days'] = RamadanGolenDay::select('user_id')
            ->groupBy('user_id')
            ->havingRaw("SUM(sunan_al_rawatib + tasbeeh + istighfar + duha_prayer + morning_evening_dhikr + shaf_and_witr + suhoor + drink_water + sleep_amount + brushing_teeth + contemplation_of_allahs_signs) = ?", [30 * 20])
            ->distinct('user_id')
            ->count();

        // 5. Number of ramadan_day_id where the user achieved a sum of 30
        $statistics['auth_golden_days'] = RamadanGolenDay::where('user_id', Auth::id())
            ->where(DB::raw("sunan_al_rawatib + tasbeeh + istighfar + duha_prayer + morning_evening_dhikr + shaf_and_witr + suhoor + drink_water + sleep_amount + brushing_teeth + contemplation_of_allahs_signs"), 30)
            ->distinct('ramadan_day_id')
            ->count();


        // 6. Summation of specific ramadan_day_id
        $statistics['auth_specific_ramadan_day_points'] = RamadanGolenDay::where('user_id', Auth::id())
            ->where('ramadan_day_id', $ramadan_day_id)
            ->sum('sunan_al_rawatib', 'tasbeeh', 'istighfar', 'duha_prayer', 'morning_evening_dhikr', 'shaf_and_witr', 'suhoor', 'drink_water', 'sleep_amount', 'brushing_teeth', 'contemplation_of_allahs_signs');


        return $this->jsonResponseWithoutMessage($statistics, 'data', 200);
    }
}
