<?php

namespace App\Http\Controllers\Api\Ramadan;

use App\Http\Controllers\Controller;
use App\Models\RamadanNightPrayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\DB;

class RamadanNightPrayerController extends Controller
{
    use ResponseJson;

    public function store(Request $request)
    {
        //validate requested data
        $validator = Validator::make($request->all(), [
            'ramadan_day_id' => 'required|exists:ramadan_days,id',
            'no_of_rakaat' => 'required|integer',
            'night_pray' => 'required',
        ]);

        //validator errors response
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }



        try {
            $user_id = Auth::id();
            $night_pray_data = [
                'user_id' => $user_id,
                'ramadan_day_id' => $request->ramadan_day_id,
                'no_of_rakaat' => $request->no_of_rakaat,
                'night_pray' => $request->night_pray,
            ];

            $night_prayers = RamadanNightPrayer::updateOrCreate(
                ['user_id' => $user_id, 'ramadan_day_id' => $request->ramadan_day_id],
                $night_pray_data
            );

            return $this->jsonResponseWithoutMessage($night_prayers, 'data', 200);
        } catch (\Exception $e) {
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    public function statistics($ramadan_day_id)
    {
        $statistics = [];
        
        // 1. Number of distinct users who complete night pray for 5 days
        $statistics['distinct_users_5_night'] = RamadanNightPrayer::where('no_of_rakaat', '>', 0)
            ->where('night_pray', 2)
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT ramadan_day_id) >= 5')
            ->count();

        // 2. Number of distinct users who complete night pray for 10 days
        $statistics['distinct_users_10_night'] = RamadanNightPrayer::where('no_of_rakaat', '>', 0)
            ->where('night_pray', 2)
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT ramadan_day_id) >= 10')
            ->count();

        // 3. Number of distinct users who complete night pray for 20 days
        $statistics['distinct_users_20_night'] = RamadanNightPrayer::where('no_of_rakaat', '>', 0)
            ->where('night_pray', 2)
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT ramadan_day_id) >= 20')
            ->count();

        // 4. Number of ramadan_day_id where the user complete night pray
        $statistics['auth_complete_nights'] = RamadanNightPrayer::where('user_id', Auth::id())
            ->where('no_of_rakaat', '>', 0)
            ->where('night_pray', 2)
            ->distinct('ramadan_day_id')
            ->count();

        // 5. Summation of specific ramadan_day_id
        $statistics['auth_specific_ramadan_day_points'] = RamadanNightPrayer::where('user_id', Auth::id())
            ->where('ramadan_day_id', $ramadan_day_id)
            ->sum(DB::raw("no_of_rakaat + night_pray"));

        return $this->jsonResponseWithoutMessage($statistics, 'data', 200);
    }


    public function show($ramadan_day_id)
    {
        $night_pray = RamadanNightPrayer::where('ramadan_day_id', $ramadan_day_id)->where('user_id', Auth::id())->first();
        return $this->jsonResponseWithoutMessage($night_pray, 'data', 200);
    }
}
