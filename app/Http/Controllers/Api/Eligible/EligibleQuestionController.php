<?php

namespace App\Http\Controllers\Api\Eligible;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EligibleGeneralInformations;
use App\Models\EligibleQuestion;
use App\Models\EligibleQuotation;
use App\Models\EligibleThesis;
use App\Models\User;
use App\Models\EligibleUserBook;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use Symfony\Component\HttpFoundation\Response;

class EligibleQuestionController extends Controller
{
    use ResponseJson;
    public function index()
    {

        $question = EligibleQuestion::all();
        return $this->jsonResponseWithoutMessage($question, 'Questions', 200);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required',
            'quotes' => 'required',
            'quotes.*.text' => 'required',
            'eligible_user_books_id' => 'required',
            "starting_page" => 'required',
            "ending_page" => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $quotationInput = $request->quotes;
        $qoutes = [];

        foreach ($quotationInput as $value) {

            $qoute = EligibleQuotation::create($value);
            array_push($qoutes, $qoute);
        }
        try {
            $newQuestion = EligibleQuestion::create([
                'question' => $request->question,
                'eligible_user_books_id' => $request->eligible_user_books_id,
                "starting_page" => $request->starting_page,
                "ending_page" => $request->ending_page

            ]);
            $newQuestion->quotation()->saveMany($qoutes);
        } catch (\Illuminate\Database\QueryException $e) {
            echo ($e);
            return $this->jsonResponseWithoutMessage('User Book does not exist.', 'data', 200);
        }
        $question = EligibleQuestion::find($newQuestion->id);
        return $this->jsonResponseWithoutMessage($question, 'data', 200);
    }


    public function show($id)
    {
        $question = EligibleQuestion::where('id', $id)->with('user_book.book')->first();

        if (is_null($question)) {
            return $this->jsonResponseWithoutMessage("Question does not exist", 'data', 200);
        }
        return $this->jsonResponseWithoutMessage($question, 'data', 200);
    }


    public function update(Request $request,  $id)
    {
        $validator = Validator::make($request->all(), [
            'question' => 'required',
            'quotes' => 'required',
            'quotes.*.text' => 'required',
            "starting_page" => 'required',
            "ending_page" => 'required'
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $input = $request->all();
        $quotationInput = $input['quotes'];
        $qoutes = [];

        try {


            $question = EligibleQuestion::find($id);
            if (Auth::id() == $question->user_book->user_id) {
                EligibleQuotation::where('eligible_question_id', $question->id)->delete();
                foreach ($quotationInput as $value) {
                    $qoute = EligibleQuotation::create($value);
                    array_push($qoutes, $qoute);
                }
                $question->question = $request->question;
                $question->starting_page = $request->starting_page;
                $question->ending_page = $request->ending_page;
                $question->quotation()->saveMany($qoutes);
                $question->save();
            }
        } catch (\Error $e) {

            return $this->jsonResponseWithoutMessage('', $e, 200);
        }
        return $this->jsonResponseWithoutMessage($question, 'data', 200);
    }

    public function destroy($id)
    {

        EligibleQuotation::where('eligible_question_id', $id)->delete();
        $result = EligibleQuestion::destroy($id);

        return $this->jsonResponseWithoutMessage("deleted", 'data', 200);
    }

    public function addDegree(Request $request,  $id)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'reviews' => 'required',
            'degree' => 'required',
            'auditor_id' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }


        $question = EligibleQuestion::find($id);

        $question->reviews = $request->reviews;
        $question->degree = $request->degree;
        $question->auditor_id = $request->auditor_id;
        $question->status = 'audited';

        try {
            $question->save();
            // Stage Up
            $auditedTheses = EligibleThesis::where('eligible_user_books_id', $question->eligible_user_books_id)->where('status', 'audited')->count();
            $auditedGeneralInfo = EligibleGeneralInformations::where('eligible_user_books_id', $question->eligible_user_books_id)->where('status', 'audited')->count();
            $auditedQuestions = EligibleQuestion::where('eligible_user_books_id', $question->eligible_user_books_id)->where('status', 'audited')->count();
            if ($auditedTheses >= 8 && $auditedQuestions >= 5 && $auditedGeneralInfo) {
                $userBook = EligibleUserBook::where('id', $question->eligible_user_books_id)->update(['status' => 'audited']);
            }
        } catch (\Error $e) {

            return $this->jsonResponseWithoutMessage('Questions does not exist', 'data', 200);
        }
        return $this->jsonResponseWithoutMessage('Degree added Successfully!', 'data', 200);
    }
    //ready to review
    public function reviewQuestion($id)
    {
        try {
            $question = EligibleQuestion::where('eligible_user_books_id', $id)->where(function ($query) {
                $query->where('status', 'retard')
                    ->orWhereNull('status');
            })->update(['status' => 'ready']);
        } catch (\Error $e) {
            return $this->jsonResponseWithoutMessage('Question does not exist', 'data', 200);
        }
    }

    public function review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required_without:eligible_user_books_id',
            'eligible_user_books_id' => 'required_without:id',
            'status' => 'required',
            'reviewer_id' => 'required',
            'reviews' => 'required_if:status,rejected'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        try {
            if ($request->has('id')) {

                $question = EligibleQuestion::find($request->id);
                $question->status = $request->status;
                $question->reviewer_id = $request->reviewer_id;
                if ($request->has('reviews')) {
                    $question->reviews = $request->reviews;
                    $userBook = EligibleUserBook::find($question->eligible_user_books_id);
                    $user = User::find($userBook->user_id);
                    $userBook->status = $request->status;
                    $userBook->reviews = $request->reviews;
                    $userBook->save();
                    $user->notify(
                        (new \App\Notifications\RejectAchievement($userBook->book->name))->delay(now()->addMinutes(2))
                    );
                }

                $question->save();
            } else if ($request->has('eligible_user_books_id')) {
                $questions = EligibleQuestion::where('eligible_user_books_id', $request->eligible_user_books_id)->update(['status' => $request->status]);
            }
        } catch (\Error $e) {
            return $this->jsonResponseWithoutMessage('Question does not exist', 'data', 200);
        }
    }



    public function finalDegree($eligible_user_books_id)
    {
        $degrees = EligibleQuestion::where("eligible_user_books_id", $eligible_user_books_id)->avg('degree');
        return $this->jsonResponseWithoutMessage($degrees, 'data', 200);
    }

    public function getUserBookQuestions($id)
    {
        $questions = EligibleQuestion::where('eligible_user_books_id', $id)->get();
        return $this->jsonResponseWithoutMessage($questions, 'data', 200);
    }
    public function getByStatus($status)
    {
        $questions =  EligibleQuestion::with("user_book.user")->with("user_book.book")->with("user_book.questions")
            ->where('status', $status)
            ->groupBy('eligible_user_books_id')
            ->orderBy('updated_at', 'asc')
            ->get();
        return $this->jsonResponseWithoutMessage($questions, 'data', 200);
    }

    public function getByUserBook($eligible_user_books_id)
    {
        $response['questions'] =  EligibleQuestion::with("user_book.user")->with("user_book.book")->with('reviewer')->with('auditor')->where('eligible_user_books_id', $eligible_user_books_id)->get();
        $response['acceptedQuestions'] =  EligibleQuestion::where('eligible_user_books_id', $eligible_user_books_id)->where('status', 'accept')->count();
        $response['userBook'] =  EligibleUserBook::find($eligible_user_books_id);
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }
    public function getByBook($book_id)
    {
        $questions['user_book'] = EligibleUserBook::where('user_id', Auth::id())->where('book_id', $book_id)->latest()->first();
        $questions['questions'] =  EligibleQuestion::with('reviewer')->with('auditor')->where('eligible_user_books_id', $questions['user_book']->id)->get();
        return $this->jsonResponseWithoutMessage($questions, 'data', 200);
    }

    public static function questionsStatistics()
    {
        $thesisCount = EligibleQuestion::count();
        $very_excellent =  EligibleQuestion::where('degree', '>=', 95)->where('degree', '<', 100)->count();
        $excellent = EligibleQuestion::where('degree', '>', 94.9)->where('degree', '<', 95)->count();
        $veryGood =  EligibleQuestion::where('degree', '>', 89.9)->where('degree', '<', 85)->count();
        $good = EligibleQuestion::where('degree', '>', 84.9)->where('degree', '<', 80)->count();
        $accebtable = EligibleQuestion::where('degree', '>', 79.9)->where('degree', '<', 70)->count();
        $rejected = EligibleQuestion::where('status', 'rejected')->count();
        return [
            "total" => $thesisCount,
            "very_excellent" => ($very_excellent / $thesisCount) * 100,
            "excellent" => ($excellent / $thesisCount) * 100,
            "veryGood" => ($veryGood / $thesisCount) * 100,
            "good" => ($good / $thesisCount) * 100,
            "accebtable" => ($accebtable / $thesisCount) * 100,
            "rejected" => ($rejected / $thesisCount) * 100,
        ];
    }

    public static function questionsStatisticsForUser($id)
    {
        $questionsCount = EligibleUserBook::join('questions', 'user_book.id', '=', 'questions.eligible_user_books_id')->where('user_id', $id)->count();
        $very_excellent =  EligibleUserBook::join('questions', 'user_book.id', '=', 'questions.eligible_user_books_id')->where('degree', '>=', 95)->where('degree', '<=', 100)->count();
        $excellent = EligibleUserBook::join('questions', 'user_book.id', '=', 'questions.eligible_user_books_id')->where('degree', '>', 94.9)->where('degree', '<', 95)->count();
        $veryGood = EligibleUserBook::join('questions', 'user_book.id', '=', 'questions.eligible_user_books_id')->where('degree', '>', 89.9)->where('degree', '<', 85)->count();
        $good = EligibleUserBook::join('questions', 'user_book.id', '=', 'questions.eligible_user_books_id')->where('degree', '>', 84.9)->where('degree', '<', 80)->count();
        $accebtable = EligibleUserBook::join('questions', 'user_book.id', '=', 'questions.eligible_user_books_id')->where('degree', '>', 79.9)->where('degree', '<', 70)->count();
        return [
            "total" => $questionsCount,
            "very_excellent" => ($very_excellent / $questionsCount) * 100,
            "excellent" => ($excellent / $questionsCount) * 100,
            "veryGood" => ($veryGood / $questionsCount) * 100,
            "good" => ($good / $questionsCount) * 100,
            "accebtable" => ($accebtable / $questionsCount) * 100,
        ];
    }
    function undoAccept(Request $request, $questionId)
    {
        if (!Auth::user()->hasanyrole('admin|super_reviewer|eligible_admin')) {
            return $this->jsonResponseWithoutMessage('لا تملك صلاحية التصحيح', 'data', Response::HTTP_FORBIDDEN);
        }
        EligibleQuestion::where('id', $questionId)
            ->update(['status' => 'review']);

        return $this->jsonResponseWithoutMessage("تم الاعادة لفريق المراجعة", 'data', 200);
    }
}
