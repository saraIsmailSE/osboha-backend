<?php

namespace App\Http\Controllers\Api\Ramadan;

use App\Http\Controllers\Controller;
use App\Models\RamadanDay;
use App\Models\RamadanNightPrayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RamadanDayController extends Controller
{
    use ResponseJson;

    public function all()
    {
        $days = RamadanDay::all();
        return $this->jsonResponseWithoutMessage($days, 'data', 200);
    }
    public function currentDay()
    {
        $current_day = RamadanDay::latest()->first();
        return $this->jsonResponseWithoutMessage($current_day, 'data', 200);
    }
    public function previousDay()
    {
        $previous_day = RamadanDay::latest()->skip(1)->take(2)->first();
        return $this->jsonResponseWithoutMessage($previous_day, 'data', 200);
    }

    public function create()
    {
        Log::channel('ramadanDay')->info('START: creating new day');

        $last_day = RamadanDay::latest()->first();

        if ($last_day) {
            //if last day is today ==> return
            if ($last_day->created_at->format('Y-m-d') == date('Y-m-d')) {
                Log::channel('ramadanDay')->info('ERROR: Ramadan day ' . $last_day->day . ' already created at ' . $last_day->created_at->format('Y-m-d H:i:s'));
                return;
            }

            //check if day to be added already added
            $addedBefore = RamadanDay::where('day', $last_day->day + 1)->whereDate('created_at', date('Y-m-d'))->first();

            if ($addedBefore) {
                Log::channel('ramadanDay')->info('ERROR: Ramadan day ' . $last_day->day + 1 . ' already created at ' . $addedBefore->created_at->format('Y-m-d H:i:s'));
                return;
            }
        }

        DB::beginTransaction();

        try {
            $new_day = new RamadanDay();
            $new_day->day = $last_day ? $last_day->day + 1 : 1;
            $new_day->save();

            DB::commit();
            Log::channel('ramadanDay')->info('END: Ramadan day ' . $new_day->day . ' created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('ramadanDay')->error('ERROR: create new day error: ' . $e->getMessage());
        }
    }
}
