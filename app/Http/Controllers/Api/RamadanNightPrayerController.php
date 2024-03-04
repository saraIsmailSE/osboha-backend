<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RamadanGolenDay;
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
            'night_pray' => 'required|integer',
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
                'no_of_rakaat' => $request->no_of_rakaat,
                'night_pray' => $request->night_pray,
            ];

            $night_prayers = RamadanNightPrayer::updateOrCreate(
                ['user_id' => $user_id, 'ramadan_day_id' => $request->ramadan_day_id],
                $golden_day_data
            );

            return $this->jsonResponseWithoutMessage($night_prayers, 'data', 200);
        } catch (\Exception $e) {
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
    }

    public function statistics($ramadan_day_id)
    {
        $statistics = [];

        return $this->jsonResponseWithoutMessage($statistics, 'data', 200);
    }
}
