<?php

namespace App\Http\Controllers\Api\Ramadan;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\RamadanQuestionsAnswer;
use App\Models\RamadanQuestion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use Carbon\Carbon;
use App\Http\Controllers\Api\NotificationController;



class RamadanQuestionAnswerController extends Controller

{
    use ResponseJson;

    function getToCorrect( $category)  {
        if (!$category) {
            return $this->jsonResponseWithoutMessage("يجب ادخال قئة السؤال " ,'data', 200);
        }
        if (!Auth::user()->hasanyrole('admin|ramadan_vedio_corrector|ramadan_fiqh_corrector|ramadan_tafseer_corrector|ramadan_coordinator')) {
            return $this->jsonResponseWithoutMessage('لا تملك صلاحية التصحيح', 'data', Response::HTTP_FORBIDDEN);
        }
        
        $answers = RamadanQuestionsAnswer::where('status','pending')->with('user')->with('reviewer')
                                          ->with('ramadanQuestion' , function ($query)  use ($category)  {
                                              $query->where('category', $category );
                                            }) ->orderBy('created_at', 'asc')->get();

        if ($answers->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage($answers, 'data', 200);
        } else {
            throw new NotFound;
        }

    }

    
    /**
     * Retrieve and return Ramadan questions for a specific day  
     * that have a time_to_publish before the current time with answer for auth user.
     * 
     * @param int $ramadan_day_id 
     * @return JsonResponse JSON response 
     * 
     * */
    function show ($ramadan_day_id) {
        if (!$ramadan_day_id) {
            return $this->jsonResponseWithoutMessage("عذراً، لم يفتح هذا اليوم بعد!" ,'data', 200);
        }

        $now = Carbon::now(); // // Get current time
       
        $ramadanDayQuestions = RamadanQuestion::where('ramadan_day_id', $ramadan_day_id)
                                                ->where('time_to_publish', '<', $now) 
                                                ->withSum('answers', 'points')
                                                ->with('answers' , function ($query) {
                                                    $query->where('user_id', Auth::id());
                                                }) 
                                            ->get();
        $totalPoint = $ramadanDayQuestions->sum( "answers_sum_points");
        $ramadanDayQuestions->totalPoint = $totalPoint;
    
        if ($ramadanDayQuestions->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage($ramadanDayQuestions, 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    /**
     * store answer 
     * *@param  request containing the ramadan_question_id, answer.
     * @return JsonResponse JSON response 
     * 
     * */
    function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'ramadan_question_id' => 'required|exists:ramadan_questions,id',
            'answer' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $user_id =Auth::id();
        $answer = RamadanQuestionsAnswer::updateOrCreate(
            ['user_id' => $user_id, 'ramadan_question_id' => $request->ramadan_question_id],
            ['answer' =>  $request->answer]
        );
 
        return $this->jsonResponseWithoutMessage("تم استلام اجابتك، سيصلك اشعار عندما يتم تصحيحها", 'data', 200);
    }

    /** Corrects an answer to a Ramadan-related question.
     *
     * * @param  request containing the answer ID, status, and reviews.
     *   @return A JSON response indicating the success of the correction process.
     */

    function correctAnswer(Request $request)  {
        $validator = Validator::make($request->all(), [
            'answer_id' => 'required|exists:ramadan_questions_answers,id',
            'status' => 'required',
            'reviews' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (!Auth::user()->hasanyrole('admin|ramadan_vedio_corrector|ramadan_fiqh_corrector|ramadan_tafseer_corrector|ramadan_coordinator')) {
            return $this->jsonResponseWithoutMessage('لا تملك صلاحية التصحيح', 'data', Response::HTTP_FORBIDDEN);
        }
        $answer = RamadanQuestionsAnswer::with('ramadanQuestion')->findOrFail($request->answer_id);

        if ($request->status == 'accepted'){
            $now = Carbon::now(); // // Get current time
            $nextTimeToPublish =  RamadanQuestion::where('time_to_publish', '>',$answer->ramadanQuestion->time_to_publish)
            ->where('ramadan_day_id', $answer->ramadanQuestion->ramadan_day_id)
            ->orderBy('time_to_publish')
            ->pluck('time_to_publish')
            ->first();
           
            if(Carbon::parse($nextTimeToPublish)  <  $answer->created_at ){
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
        $msg = "تم تصحيح السؤال  " . $answer->ramadanQuestion->title . " وقد حصلت على " .  $points . " نقطة إضافية." ;
        (new NotificationController)->sendNotification($answer->user_id, $msg, ROLES);


        return $this->jsonResponseWithoutMessage( 'تم اعتماد التصحيح', 'data', 200);
        
    } 

}
