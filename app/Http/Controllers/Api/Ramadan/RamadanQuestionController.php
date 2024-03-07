<?php

namespace App\Http\Controllers\Api\Ramadan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\RamadanQuestion;


class RamadanQuestionController extends Controller
{
    use ResponseJson;

    /**
     * Retrieve and return Ramadan questions for a specific day  that have a time_to_publish before the current time with answer for auth user.
     * 
     * @param int $ramadan_day_id 
     * @return JsonResponse JSON response containing the questions if available, or throws a NotFound exception if no questions are found.
     */
    function show ($ramadan_day_id) {
        $now = Carbon::now(); // // Get current time
       
        $ramadanDayQuestions = RamadanQuestion::where('ramadan_day_id', $ramadan_day_id)
                                                ->where('time_to_publish', '<', $now) 
                                                ->with('answerAuthUser')
                                                ->get();

        if ($ramadanDayQuestions->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage($ramadanDayQuestions, 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    
    function getbyCorrect( $category)  {
        //status:'pending', 'accepted', 'rejected'
        //category: التثقيف بالفيديو - تفسير - فقه
        // ramadan_tafseer_corrector - ramadan_vedio_corrector
        
        if(Auth::user()->hasRole('ramadan_fiqh_corrector')) {
            $category = 'ramadan_vedio_corrector' ;
        } 
        elseif (Auth::user()->hasRole('ramadan_vedio_corrector')) {
            $category = 'ramadan_vedio_corrector' ;
        } 
        elseif (Auth::user()->hasRole('ramadan_tafseer_corrector ')) {
            $category = 'تفسير' ;
        }
        else {
            throw new NotAuthorized;
        }
            $questions = RamadanQuestion::where('category', $category)
                                        ->with('answers', function ($query) {
                                            $query->where('status','!=' ,'accepted'); //'pending', 'accepted', 'rejected'
                                        })
                                        ->get();
            if ($questions->isNotEmpty()) {
                return $this->jsonResponseWithoutMessage($questions, 'data', 200);
            } else {
                throw new NotFound;
            }
        
    }

    function correctHadith($status)  {
        if ($status == 'accepted'){
            
        }
        
    }

}
