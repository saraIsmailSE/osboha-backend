<?php

namespace App\Http\Controllers;

use App\Exceptions\NotAuthorized;
use App\Http\Resources\UserInfoResource;
use App\Models\Question;
use App\Models\QuestionFollowup;
use App\Models\Week;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use function PHPUnit\Framework\isEmpty;

class QuestionFollowupController extends Controller
{
    use ResponseJson;
    public function addFollowup()
    {
        $currentWeek = Week::latest()->first();

        $followup = QuestionFollowup::where('user_id', Auth::id())
            ->where('date', Carbon::now()->format('Y-m-d'))
            ->first();

        if ($followup && $followup->counter < 2) {
            //check the last update, if it is greater than 6 hrs, update counter
            $lastUpdate = Carbon::parse($followup->updated_at);
            $now = Carbon::now();
            $diff = $lastUpdate->diffInHours($now);

            if ($diff >= 6) {
                $followup->counter = $followup->counter + 1;
                $followup->save();
            } else {
                return  $this->jsonResponseWithoutMessage('لا يمكنك التفقد قبل مرور 6 ساعات من آخر تفقد', 'data', 422);
            }
        } else if (!$followup) {
            $followup = QuestionFollowup::create([
                'user_id' => Auth::id(),
                'counter' => 1,
                'date' => Carbon::now()->format('Y-m-d'),
                'week_id' => $currentWeek->id,
            ]);
        } else {
            return  $this->jsonResponseWithoutMessage('لا يمكنك التفقد أكثر من مرتين في اليوم', 'data', 422);
        }

        return  $this->jsonResponseWithoutMessage($followup, 'data', 200);
    }

    public function getFollowupStatistics()
    {
        if (!Auth::user()->hasRole('admin')) {
            throw new NotAuthorized;
        }

        $currentWeek = Week::latest()->first();
        $followups = QuestionFollowup::where('week_id', $currentWeek->id)->get();

        if ($followups->isEmpty()) {
            dd($followups);
            return  $this->jsonResponseWithoutMessage([], 'data', 200);
        }

        //group by user then group by day sorted by counter desc then by user role then by user name
        $followups = $followups->groupBy('user_id')
            ->values()
            ->map(function ($userGroup) {
                $days = $userGroup->groupBy(function ($item) {
                    return Carbon::parse($item->date)->dayOfWeek + 1;
                })->map(function ($dayGroup) {
                    return [
                        'counter' => $dayGroup->sum('counter'),
                    ];
                });
                $user = $userGroup->first()->user;
                return [
                    "user" => UserInfoResource::make($user),
                    "days" => $days,
                ];
            });

        return $this->jsonResponseWithoutMessage($followups, 'data', 200);
    }
}
