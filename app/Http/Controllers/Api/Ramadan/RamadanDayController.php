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
        $current_day = RamadanDay::latest()->first();
        return $this->jsonResponseWithoutMessage($current_day, 'data', 200);
    }
    public function previousDay()
    {
        $previous_day = RamadanDay::latest()->skip(1)->take(2)->first();
        return $this->jsonResponseWithoutMessage($previous_day, 'data', 200);
    }

    public function createRamadanDays()
    {

        $ramadanDays = [];
        $daysCount = 30;

        //start from 10th of March at 6:00 AM
        $startDate = Carbon::create(2024, 3, 10, 6, 0, 0);

        for ($i = 1; $i <= $daysCount; $i++) {
            $ramadanDays[] = [
                'day' => $i, 'is_active' => $i == 1 ? 1 : 0,
                'created_at' => $startDate->addDay()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ];
        }

        DB::beginTransaction();

        try {
            RamadanDay::insert($ramadanDays);
            DB::commit();
            return $this->jsonResponseWithoutMessage('Ramadan days created successfully', 'data', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->jsonResponseWithoutMessage($e->getMessage(), 'data', 500);
        }
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
