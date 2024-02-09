<?php

namespace App\Http\Controllers\Api\Eligible;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EligibleGeneralInformations;
use App\Models\EligibleQuestion;
use App\Models\EligibleThesis;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\EligibleUserBook;
use App\Traits\ResponseJson;


class EligibleGeneralInformationsController extends Controller
{
    use ResponseJson;

    public function index()
    {

        $general_informations = EligibleGeneralInformations::all();
        return $this->jsonResponseWithoutMessage($general_informations, 'data', 200);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'general_question' => 'required',
            'summary' => 'required',
            'eligible_user_books_id' => 'required',

        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $input = $request->all();

        try {
            $general_informations = EligibleGeneralInformations::create($input);
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->jsonResponseWithoutMessage('User Book does not exist', 'data', 404);
        }
        return $this->jsonResponseWithoutMessage($general_informations, 'data', 200);
    }


    public function show($id)
    {
        $general_informations = EligibleGeneralInformations::where('id', $id)->with('user_book.book')->first();

        if (is_null($general_informations)) {

            return $this->sendError('General Informations does not exist');
        }
        return $this->jsonResponseWithoutMessage($general_informations, 'data', 200);
    }


    public function update(Request $request,  $id)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'general_question' => 'required',
            'summary' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        try {
            $general_informations = EligibleGeneralInformations::find($id);
            if (Auth::id() == $general_informations->user_book->user_id) {

                $general_informations->update($request->all());
            } else {
                $general_informations->reviews = $request->reviews;
                $general_informations->degree = $request->degree;
                $general_informations->auditor_id = $request->auditor_id;
                $general_informations->status = 'audited';
                $general_informations->save();
            }
        } catch (\Error $e) {
            return $this->jsonResponseWithoutMessage('General Informations does not exist', $e, 200);
        }
        return $this->jsonResponseWithoutMessage($general_informations, 'data', 200);
    }
    public function updateOrcreat(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'general_question' => 'required',
            'summary' => 'required',
            'eligible_user_books_id' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (Auth::id() == $general_informations->user_book->user_id){
            EligibleGeneralInformations::updateOrCreate($input);
        }
        else{
            $input->status = 'audited';
            EligibleGeneralInformations::updateOrCreate($input);
        }         
    }
    public function destroy($id)
    {

        $result = EligibleGeneralInformations::destroy($id);

        if ($result == 0) {

            return $this->jsonResponseWithoutMessage($result, 'data', 200);
        }
        return $this->jsonResponseWithoutMessage($result, 'data', 200);
    }

    //ready to review
    public function reviewGeneralInformations($id)
    {
        try {
            $general_informations = EligibleGeneralInformations::where('eligible_user_books_id', $id)->update(['status' => 'ready']);
            return $this->jsonResponseWithoutMessage($general_informations, 'data', 200);
        } catch (\Error $e) {
            return $this->jsonResponseWithoutMessage($e, 'General Informations does not exist', 200);
        }
    }


    public function review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'status' => 'required',
            'reviewer_id' => 'required',
            'reviews' => 'required_if:status,rejected'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        try {
            $info = EligibleGeneralInformations::find($request->id);
            $info->status = $request->status;
            $info->reviewer_id = $request->reviewer_id;
            if ($request->has('reviews')) {
                //REJECT OR RETARD
                $info->reviews = $request->reviews;
                $userBook = EligibleUserBook::find($info->eligible_user_books_id);
                $user = User::find($userBook->user_id);
                $userBook->status = $request->status;
                $userBook->reviews = $request->reviews;
                $userBook->save();
                if ($request->status == 'rejected') {
                    $theses = EligibleThesis::where('eligible_user_books_id', $info->eligible_user_books_id)->update(['status' => $request->status, 'reviews' => $request->reviews]);
                    $questions = EligibleQuestion::where('eligible_user_books_id', $info->eligible_user_books_id)->update(['status' => $request->status, 'reviews' => $request->reviews]);
                }
                $user->notify(
                    (new \App\Notifications\RejectAchievement())->delay(now()->addMinutes(2))
                );
            }

            $info->save();
        } catch (\Error $e) {
            return $this->jsonResponseWithoutMessage('General Informations does not exist', $e, 200);
        }
    }
    public function addDegree(Request $request,  $id)
    {
        $validator = Validator::make($request->all(), [
            'reviews' => 'required',
            'degree' => 'required',
            'auditor_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }


        $general_informations = EligibleGeneralInformations::find($id);
        $general_informations->reviews = $request->reviews;

        $general_informations->degree = $request->degree;
        $general_informations->auditor_id = $request->auditor_id;
        $general_informations->status = 'audited';
        try {
            $general_informations->save();

            // Stage Up
            $auditedTheses = EligibleThesis::where('eligible_user_books_id', $general_informations->eligible_user_books_id)->where('status', 'audited')->count();
            $auditedQuestions = EligibleQuestion::where('eligible_user_books_id', $general_informations->eligible_user_books_id)->where('status', 'audited')->count();
            if ($auditedTheses >= 8 && $auditedQuestions >= 5) {
                $userBook = EligibleUserBook::where('id', $general_informations->eligible_user_books_id)->update(['status' => 'audited']);
            }
        } catch (\Error $e) {
            return $this->jsonResponseWithoutMessage($e, 'General Informations does not exist', 500);
        }
        return $this->jsonResponseWithoutMessage($general_informations, 'data', 200);
    }



    public function finalDegree($eligible_user_books_id)
    {

        $degrees = EligibleGeneralInformations::where("eligible_user_books_id", $eligible_user_books_id)->avg('degree');
        return $this->jsonResponseWithoutMessage($degrees, 'data', 200);
    }

    // DUPLICATED

    public function getByUserBookId($eligible_user_books_id)
    {
        $general_informations = EligibleGeneralInformations::where('eligible_user_books_id', $eligible_user_books_id)->first();
        return $this->jsonResponseWithoutMessage($general_informations, 'data', 200);
    }
    public function getByStatus($status)
    {
        $general_informations =  EligibleGeneralInformations::with("user_book.user")->with("user_book.book")->where('status', $status)->groupBy('eligible_user_books_id')->get();
        return $this->jsonResponseWithoutMessage($general_informations, 'data', 200);
    }


    public function getByUserBook($eligible_user_books_id)
    {
        $general_informations =  EligibleGeneralInformations::with("user_book.user")
            ->with("user_book.book")->with('reviewer')->with('auditor')->where('eligible_user_books_id', $eligible_user_books_id)->first();
        return $this->jsonResponseWithoutMessage($general_informations, 'data', 200);
    }
    public function getByBook($book_id)
    {
        $general_informations['user_book'] = EligibleUserBook::where('user_id', Auth::id())->where('book_id', $book_id)->first();
        $general_informations['general_informations'] =  EligibleGeneralInformations::with('reviewer')->with('auditor')
            ->where('eligible_user_books_id', $general_informations['user_book']->id)->first();
        return $this->jsonResponseWithoutMessage($general_informations, 'data', 200);
    }



    public static function generalInformationsStatistics()
    {
        $generalInformationsCount = EligibleGeneralInformations::count();
        $very_excellent =  EligibleGeneralInformations::where('degree', '>=', 95)->where('degree', '<', 100)->count();
        $excellent = EligibleGeneralInformations::where('degree', '>', 94.9)->where('degree', '<', 95)->count();
        $veryGood =  EligibleGeneralInformations::where('degree', '>', 89.9)->where('degree', '<', 85)->count();
        $good = EligibleGeneralInformations::where('degree', '>', 84.9)->where('degree', '<', 80)->count();
        $accebtable = EligibleGeneralInformations::where('degree', '>', 79.9)->where('degree', '<', 70)->count();
        $rejected = EligibleGeneralInformations::where('status', 'rejected')->count();
        return [
            "total" => $generalInformationsCount,
            "very_excellent" => ($very_excellent / $generalInformationsCount) * 100,
            "excellent" => ($excellent / $generalInformationsCount) * 100,
            "very_good" => ($veryGood / $generalInformationsCount) * 100,
            "good" => ($good / $generalInformationsCount) * 100,
            "accebtable" => ($accebtable / $generalInformationsCount) * 100,
            "rejected" => ($rejected / $generalInformationsCount) * 100,
        ];
    }

    public static function generalInformationsStatisticsForUser($id)
    {
        $generalInformationsCount = EligibleUserBook::join('eligible_general_informations', 'eligible_user_books.id', '=', 'eligible_general_informations.eligible_user_books_id')->where('user_id', $id)->count();
        $very_excellent =  EligibleUserBook::join('eligible_general_informations', 'eligible_user_books.id', '=', 'eligible_general_informations.eligible_user_books_id')->where('degree', '>=', 95)->where('degree', '<=', 100)->count();
        $excellent = EligibleUserBook::join('eligible_general_informations', 'eligible_user_books.id', '=', 'eligible_general_informations.eligible_user_books_id')->where('degree', '>', 94.9)->where('degree', '<', 95)->count();
        $veryGood =  EligibleUserBook::join('eligible_general_informations', 'eligible_user_books.id', '=', 'eligible_general_informations.eligible_user_books_id')->where('degree', '>', 89.9)->where('degree', '<', 85)->count();
        $good = EligibleUserBook::join('eligible_general_informations', 'eligible_user_books.id', '=', 'eligible_general_informations.eligible_user_books_id')->where('degree', '>', 84.9)->where('degree', '<', 80)->count();
        $accebtable = EligibleUserBook::join('eligible_general_informations', 'eligible_user_books.id', '=', 'eligible_general_informations.eligible_user_books_id')->where('degree', '>', 79.9)->where('degree', '<', 70)->count();
        return [
            "total" => $generalInformationsCount,
            "very_excellent" => ($very_excellent / $generalInformationsCount) * 100,
            "excellent" => ($excellent / $generalInformationsCount) * 100,
            "very_good" => ($veryGood / $generalInformationsCount) * 100,
            "good" => ($good / $generalInformationsCount) * 100,
            "accebtable" => ($accebtable / $generalInformationsCount) * 100
        ];
    }
}
