<?php

namespace App\Http\Controllers\Api\Ramadan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use Carbon\Doctrine\CarbonDoctrineType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\RamadanQuranWird;

class RamadanQuranWirdController extends Controller
{

    /**
     * @author Rufaidah
     * store a number of juzu
     *
     * @param Request $request
     * @return ResponseJson
     */
    use ResponseJson;
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'no_of_parts' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        try {

            $user_id = Auth::id();
            $ramadan_alwird = [
                'user_id' => $user_id,
                'ramadan_day_id' => $request->ramadan_day_id,
                'no_of_parts' => $request->no_of_parts,
            ];

            $ramadan_alwird_day = RamadanQuranWird::updateOrCreate(
                ['user_id' => $user_id, 'ramadan_day_id' => $request->ramadan_day_id],
                $ramadan_alwird
            );

            return $this->jsonResponseWithoutMessage($ramadan_alwird_day, 'data', 200);
        } catch (\Exception $e) {
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }
    public function statistics($ramadan_day_id)
    {
        /*count of users who read at least one juzu
        * count of users who read more than one juzu
        * count of users who read 5 juzu or more
        */

        $statistics = [];

        // 1. Number of  users who read at least one juzu
        $statistics['num_users_read_one_juzu'] = RamadanQuranWird::where('no_of_parts', '=', 1)
            ->where('ramadan_day_id', $ramadan_day_id)
            ->count();

        // 2. Number of users who read more than one juzu
        $statistics['num_users_read_more_than_one_juzu'] = RamadanQuranWird::whereBetween('no_of_parts', [2, 4])
            ->where('ramadan_day_id', $ramadan_day_id)
            ->count();

        // 3. Number of users who read 5 juzu or more
        $statistics['num_users_read_five_juzu_or_more'] = RamadanQuranWird::where('no_of_parts', '>', 4)
            ->where('ramadan_day_id', $ramadan_day_id)
            ->count();

        // 5. Summation of Auth user
        $statistics['auth_specific_ramadan_alwird_points'] = RamadanQuranWird::where('user_id', Auth::id())
            ->where('ramadan_day_id', $ramadan_day_id)
            ->count();


        return $this->jsonResponseWithoutMessage($statistics, 'data', 200);
    }
    public function show($ramadan_day_id)
    {
        $night_pray = RamadanQuranWird::where('ramadan_day_id', $ramadan_day_id)->where('user_id', Auth::id())->first();
        return $this->jsonResponseWithoutMessage($night_pray, 'data', 200);
    }
}
