<?php

namespace App\Http\Controllers\Api\Ramadan;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\RamadanQuestionsAnswer;
use App\Models\RamadanQuestion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use App\Traits\PathTrait;
use Carbon\Carbon;
use App\Http\Controllers\Api\NotificationController;



class RamadanQuestionAnswerController extends Controller

{
    use ResponseJson, PathTrait;

    function getPending($category)
    {
        if (!Auth::user()->hasanyrole('admin|ramadan_vedio_corrector|ramadan_fiqh_corrector|ramadan_tafseer_corrector|ramadan_coordinator')) {
            return $this->jsonResponseWithoutMessage('لا تملك صلاحية التصحيح', 'data', 403);
        }

        switch ($category) {
            case 'فقه':
                $questionCategory = ['فقه'];
                break;
            case 'تفسير':
                $questionCategory = ['تفسير'];
                break;
            case 'التثقيف بالفيديو':
                $questionCategory = ['التثقيف بالفيديو'];
                break;
            default:
                $questionCategory = [
                    'التثقيف بالفيديو',
                    'فقه',
                    'تفسير',
                ];
        }

        $currentYear = now()->year;

        $answers = RamadanQuestionsAnswer::where('status', 'pending')->whereYear('created_at', $currentYear)
            ->with('user')->with('ramadanQuestion')
            ->whereHas('ramadanQuestion', function ($q) use ($questionCategory) {
                $q->whereIn('category', $questionCategory);
            })
            ->orderBy('created_at', 'asc')->get();

        return $this->jsonResponseWithoutMessage($answers, 'data', 200);
    }


    /**
     * Retrieve Answer.
     *
     * @param int $ramadan_day_id
     * @return JsonResponse JSON response
     *
     * */
    function show($id)
    {
        $answer = RamadanQuestionsAnswer::with('user')->with('reviewer')->with('ramadanQuestion')->find($id);
        return $this->jsonResponseWithoutMessage($answer, 'data', 200);
    }

    /**
     * store answer
     * *@param  request containing the ramadan_question_id, answer.
     * @return JsonResponse JSON response
     *
     * */
    function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question_id' => 'required|exists:ramadan_questions,id',
            'answer' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $user_id = Auth::id();
        $answer = RamadanQuestionsAnswer::updateOrCreate(
            ['user_id' => $user_id, 'ramadan_question_id' => $request->question_id],
            ['answer' =>  $request->answer]
        );

        return $this->jsonResponseWithoutMessage($answer, 'data', 200);
    }

    /** Corrects an answer to a Ramadan-related question.
     *
     * * @param  request containing the answer ID, status, and reviews.
     *   @return A JSON response indicating the success of the correction process.
     */

    function correctAnswer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'answer_id' => 'required|exists:ramadan_questions_answers,id',
            'status' => 'required',
            'reviews' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (!Auth::user()->hasanyrole('admin|ramadan_vedio_corrector|ramadan_fiqh_corrector|ramadan_tafseer_corrector|ramadan_coordinator')) {
            return $this->jsonResponseWithoutMessage('لا تملك صلاحية التصحيح', 'data', 403);
        }
        $answer = RamadanQuestionsAnswer::with('user')->with('reviewer')->with('ramadanQuestion')->findOrFail($request->answer_id);

        if ($request->status == 'accepted') {
            $now = Carbon::now(); // // Get current time
            $nextTimeToPublish =  RamadanQuestion::where('time_to_publish', '>', $answer->ramadanQuestion->time_to_publish)
                ->where('ramadan_day_id', $answer->ramadanQuestion->ramadan_day_id)
                ->orderBy('time_to_publish')
                ->pluck('time_to_publish')
                ->first();

            if (Carbon::parse($nextTimeToPublish)  <  $answer->created_at) {
                $points = 1;
            } else {
                $points = 3;
            }
        } else {
            $points = 0;
        }

        $answer->update([
            'status' => $request->status,
            'points' => $points,
            'reviews' => $request->reviews,
            'reviewer_id' => Auth::id(),
        ]);

        $answer->fresh();

        $msg = "تم تصحيح اجابتك على سؤال ال" . $answer->ramadanQuestion->category . " وقد حصلت على " .  $points . " نقطة إضافية.";
        (new NotificationController)->sendNotification($answer->user->id, $msg, ROLES, $this->getQuestionPath($answer->ramadanQuestion->id));


        return $this->jsonResponseWithoutMessage($answer, 'data', 200);
    }
}
