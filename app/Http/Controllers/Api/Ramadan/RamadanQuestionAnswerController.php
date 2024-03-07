<?php

namespace App\Http\Controllers\Api\Ramadan;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\RamadanQuestionsAnswer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;


class RamadanQuestionAnswerController extends Controller

{
    use ResponseJson;

    function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'ramadan_question_id' => 'required', //|exists:ramadan_days,id',
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
 
        return $this->jsonResponseWithoutMessage($answer, 'data', 200);
    }

}
