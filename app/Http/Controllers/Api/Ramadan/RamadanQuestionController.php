<?php

namespace App\Http\Controllers\Api\Ramadan;

use App\Http\Controllers\Controller;
use App\Models\RamadanDay;
use App\Models\RamadanHadith;
use App\Models\RamadanQuestion;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RamadanQuestionController extends Controller
{
    use ResponseJson;

    /**
     * @author Asmaa
     * Get day's hadiths
     *
     * @param int $dayId
     *
     * @return ResponseJson
     */
    public function getQuestionsByDay($dayId)
    {
        $day = RamadanDay::find($dayId);
        if (!$day) {
            return $this->jsonResponseWithoutMessage('اليوم غير موجود', 'data', Response::HTTP_NOT_FOUND);
        }

        $data = $day->ramadanQuestions()->with(['answers' => function ($query) {
            $query->where('user_id', Auth::id());
        }])->orderBy('time_to_publish', 'asc')
        ->get();

        return $this->jsonResponseWithoutMessage($data, 'data', Response::HTTP_OK);
    }
    public function show($id)
    {
        $question = RamadanQuestion::with('ramadanDay')->with(['answers' => function ($query) {
            $query->where('user_id', Auth::id());
        }])->find($id);

        return $this->jsonResponseWithoutMessage($question, 'data', Response::HTTP_OK);
    }
}
