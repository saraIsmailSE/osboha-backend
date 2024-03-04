<?php

namespace App\Http\Controllers\Api\Ramadan;

use App\Http\Controllers\Controller;
use App\Models\RamadanDay;
use App\Models\RamadanNightPrayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;

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
}
