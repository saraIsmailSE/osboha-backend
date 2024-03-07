<?php

namespace App\Http\Controllers\Api\Ramadan;

use App\Http\Controllers\Controller;
use App\Models\RamadanDay;
use App\Models\RamadanNightPrayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use Carbon\Doctrine\CarbonDoctrineType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RamadanDayController extends Controller
{
    use ResponseJson;

    public function all()
    {
        //get days which are active and before the active day
        $activeDay = RamadanDay::where('is_active', 1)->latest()->first();

        if (!$activeDay) {
            return $this->jsonResponseWithoutMessage('No active day found', 'data', 404);
        }

        $currentYear = Carbon::now()->year;
        $days = RamadanDay::where('day', '<=', $activeDay->day)->whereYear('created_at', $currentYear)
            ->orderBy('day', 'asc')
            ->get();

        return $this->jsonResponseWithoutMessage($days, 'data', 200);
    }
    public function currentDay()
    {
        $current_day = RamadanDay::latest()->where('is_active', 1)->first();
        return $this->jsonResponseWithoutMessage($current_day, 'data', 200);
    }
    public function previousDay()
    {
        $previous_day = RamadanDay::latest()->where('is_active', 1)->skip(1)->take(2)->first();
        return $this->jsonResponseWithoutMessage($previous_day, 'data', 200);
    }

    public function dayById($id){
        $night_pray = RamadanDay::find($id);
        return $this->jsonResponseWithoutMessage($night_pray, 'data', 200);

    }
    public function closeDay()
    {
        Log::channel('ramadanDay')->info('START: Closing the previous day and opening the next day of Ramadan');

        $activeDay = RamadanDay::where('is_active', 1)->first();
        DB::beginTransaction();

        try {

            if ($activeDay) {
                $activeDay->is_active = 0;
                $activeDay->save();
            }

            $nextDay = RamadanDay::where('day', $activeDay->day + 1)->first();

            if ($nextDay) {
                $nextDay->is_active = 1;
                $nextDay->save();
            }

            DB::commit();
            Log::channel('ramadanDay')->info('SUCCESS: Closing the previous day and opening the next day of Ramadan');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('ramadanDay')->error('ERROR: Closing the previous day and opening the next day of Ramadan');
        }
    }
}
