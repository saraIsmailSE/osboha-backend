<?php

namespace App\Http\Controllers\Api\Eligible;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\EligibleCertificates;
use App\Models\EligibleGeneralInformations;
use App\Models\EligibleQuestion;
use App\Models\EligibleThesis;
use App\Models\User;
use App\Models\EligibleUserBook;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Traits\ResponseJson;



class EligibleUserBookController extends Controller
{

    use ResponseJson;
    public function index()
    {
        $userbook = EligibleUserBook::all();
        return $this->jsonResponseWithoutMessage($userbook, 'data', 200);
    }

    public function checkAchievement($id)
    {

        $already_have_one = EligibleUserBook::where('user_id', Auth::id())->where(function ($query) {
            $query->where('status', '!=', 'finished')
                ->where('status', '!=', 'rejected')
                ->orWhereNull('status');
        })->get();
        return $this->jsonResponseWithoutMessage($already_have_one, 'data', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $count = EligibleUserBook::where(function ($query) {
            $query->where('status', '!=', 'finished')
                ->Where('status', '!=', 'rejected')
                ->WhereNull('status');
        })->where('user_id', Auth::id())->count();

        if ($count > 0) {


            return $this->jsonResponseWithoutMessage('You have an open book', 'data', 200);
        }
        try {
            $userBook = EligibleUserBook::firstOrCreate([
                'book_id' => $request->book_id,
                'user_id' => Auth::id()
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            echo ($e);
            return $this->jsonResponseWithoutMessage('User or book does not exist', 'data', 200);
        }
        return $this->jsonResponseWithoutMessage($userBook, 'data', 200);
    }


    public function getByBookID($bookId)
    {
        $userBook['userBook'] = EligibleUserBook::where('book_id', $bookId)->where('user_id', Auth::id())->first();
        $userBook['completionPercentage'] = 10;

        //50 \ 8 => 6.25 for each (50%)
        $theses = EligibleThesis::where('eligible_user_books_id', $userBook['userBook']->id)->where(function ($query) {
            $query->where('status', '!=', 'retard')->where('status', '!=', 'rejected')->orWhereNull('status');
        })->count();

        if ($theses > 8) {
            $userBook['completionPercentage'] = $userBook['completionPercentage'] + (6.25 * 8);
        } else {
            $userBook['completionPercentage'] = $userBook['completionPercentage'] + (6.25 * $theses);
        }
        //25 \ 5 => 5 for each (25%)
        $questions = EligibleQuestion::where('eligible_user_books_id', $userBook['userBook']->id)->where(function ($query) {
            $query->where('status', '!=', 'retard')->where('status', '!=', 'rejected')->orWhereNull('status');
        })->count();
        if ($questions > 5) {
            $userBook['completionPercentage'] = $userBook['completionPercentage'] + (5 * 5);
        } else {
            $userBook['completionPercentage'] = $userBook['completionPercentage'] + (5 * $questions);
        }
        $generalInformations = EligibleGeneralInformations::where('eligible_user_books_id', $userBook['userBook']->id)->where(function ($query) {
            $query->where('status', '!=', 'retard')->where('status', '!=', 'rejected')->orWhereNull('status');
        })->count();
        $userBook['completionPercentage'] = $userBook['completionPercentage'] + (15 * $generalInformations); // only one  (15%)

        return $this->jsonResponseWithoutMessage($userBook, 'data', 200);
    }

    public function show($id)
    {
        $userBook = EligibleUserBook::find($id);
        if (is_null($userBook)) {
            return $this->jsonResponseWithoutMessage('UserBook does not exist', 'data', 200);
        }
        return $userBook;
    }

    public function lastAchievement()
    {

        $userBook['last_achievement'] = EligibleUserBook::where('user_id', Auth::id())->latest()->first();
        $userBook['statistics'] = EligibleUserBook::join('eligible_general_informations', 'eligible_user_books.id', '=', 'eligible_general_informations.user_books_id')
            ->join('eligible_questions', 'eligible_user_book.id', '=', 'eligible_questions.user_books_id')
            ->join('eligible_thesis', 'eligible_user_books.id', '=', 'eligible_thesis.user_books_id')
            ->select(DB::raw('avg(eligible_general_informations.degree) as general_informations_degree,avg(eligible_questions.degree) as questions_degree,avg(eligible_thesis.degree) as thesises_degree'))
            ->where('user_id', Auth::id())
            ->orderBy('eligible_user_books.created_at', 'desc')->first();

        return $this->jsonResponseWithoutMessage($userBook, 'data', 200);
    }


    public function finishedAchievement()
    {
        $userBook = EligibleUserBook::where('user_id', Auth::id())->where('status', 'finished')->get();
        return $this->jsonResponseWithoutMessage($userBook, 'data', 200);
    }

    //need test
    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'status' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        try {
            $userBook = EligibleUserBook::find($id);
            $userBook->status = $request->status;
            $userBook->save();
            $user = User::find($userBook->user_id);
            $theses = EligibleThesis::where('eligible_user_books_id', $id)->where('status', 'ready')->update(['status' => $request->status]);
            $questions = EligibleQuestion::where('eligible_user_books_id', $id)->where('status', 'ready')->update(['status' => $request->status]);
            $generalInformations = EligibleGeneralInformations::where('eligible_user_books_id', $id)->where('status', 'ready')->update(['status' => $request->status]);
            if ($request->status == 'review') {
                $user->notify(
                    (new \App\Notifications\SetAchievement())->delay(now()->addMinutes(2))
                );
            } else {
                $user->notify(
                    (new \App\Notifications\RejectAchievement())->delay(now()->addMinutes(2))
                );
            }
        } catch (\Error $e) {
            return 'erroe';
        }
        return $this->jsonResponseWithoutMessage($userBook, 'data', 200);
    }

    public function destroy($id)
    {
        $userBook = EligibleUserBook::find($id);

        if (!$userBook) {
            return $this->jsonResponseWithoutMessage('UserBook does not exist', 'data', 200);
        }

        if (is_null($userBook->status)) {
            $userBook->delete();
            return $this->jsonResponseWithoutMessage('UserBook deleted successfully!', 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage('UserBook status is not null', 'data', 200);
        }
    }


    public function changeStatus(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($request->all(), [
            'status' => 'required'
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }


        $userBook = EligibleUserBook::find($id);

        $userBook->status = $input['status'];
        try {
            $user = User::find($userBook->user_id);
            $userBook->save();
            if ($userBook->status == 'rejected' || $userBook->status == 'retard')
                $user->notify(
                    (new \App\Notifications\RejectAchievement())->delay(now()->addMinutes(2))
                );
        } catch (\Error $e) {
            return $this->jsonResponseWithoutMessage('UserBook does not exist', 'data', 200);
        }
        return $this->jsonResponseWithoutMessage($userBook, 'UserBook updated Successfully!', 'data', 200);
    }
    public function review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage('errors', $validator->errors(), 'data', 200);
        }

        try {
            //REJECT OR RETARD ENTIER USER BOOK
            $userBook = EligibleUserBook::find($request->id);
            return $userBook;
            $user = User::find($userBook->user_id);
            $userBook->status = $request->status;
            $userBook->reviews = $request->reviews;
            $userBook->save();
            $theses = EligibleThesis::where('eligible_user_books_id', $request->id)->update(['status' => $request->status, 'reviews' => $request->reviews]);
            $questions = EligibleQuestion::where('eligible_user_books_id', $request->id)->update(['status' => $request->status, 'reviews' => $request->reviews]);
            $generalInformations = EligibleGeneralInformations::where('eligible_user_books_id', $request->id)->update(['status' => $request->status, 'reviews' => $request->reviews]);
        } catch (\Error $e) {
            return $this->jsonResponseWithoutMessage('User Book does not exist', 'data', 200);
        }
    }


    public function checkOpenBook()
    {
        $id = Auth::id();

        $open_book = EligibleUserBook::where('user_id', $id)->where('status', '!=', 'finished')->count();


        return $this->jsonResponseWithoutMessage($open_book, 'Open Book', 'data', 200);
    }


    public function getStageStatus($id)
    {

        $thesis = EligibleThesis::where('eligible_user_books_id', $id)->where('status', 'audit')->exists();
        $question = EligibleQuestion::where('eligible_user_books_id', $id)->where('status', 'audit')->exists();
        $status = $thesis + $question;


        return $this->sendResponse($status, 'Status');
    }

    public function readyToAudit()
    {

        $readyToAudit['theses'] = DB::select("SELECT eligible_user_books_id FROM(SELECT COUNT(id) AS totalThesis, eligible_user_books_id FROM thesis WHERE STATUS = 'accept' GROUP BY eligible_user_books_id) AS b WHERE b.totalThesis>=8");
        $readyToAudit['questions'] = DB::select("SELECT eligible_user_books_id FROM(SELECT COUNT(id) AS totalQuestions, eligible_user_books_id FROM questions WHERE STATUS = 'accept' GROUP BY eligible_user_books_id) AS b WHERE b.totalQuestions >=5");
        return $this->jsonResponseWithoutMessage($readyToAudit, 'data', 200);
    }

    public function checkCertificate($id)
    {
        $status = EligibleCertificates::where('eligible_user_books_id', $id)->exists();
        return $this->sendResponse($status, 'Status');
    }

    public function getStatistics($id)
    {
        $thesisFinalDegree = EligibleThesis::where("eligible_user_books_id", $id)->avg('degree');
        $questionFinalDegree = EligibleQuestion::where("eligible_user_books_id", $id)->avg('degree');
        $generalInformationsFinalDegree = EligibleGeneralInformations::where("eligible_user_books_id", $id)->avg('degree');
        $finalDegree = ($thesisFinalDegree + $questionFinalDegree + $generalInformationsFinalDegree) / 3;
        $response = [
            "thesises" => intval($thesisFinalDegree),
            "questions" => intval($questionFinalDegree),
            "general_informations" => intval($generalInformationsFinalDegree),
            "final" => intval($finalDegree),
        ];
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    public function getGeneralstatistics()
    {
        $thesis = EligibleThesis::thesisStatistics();
        $questions = EligibleQuestionController::questionsStatistics();
        $generalInformations = EligibleGeneralInformations::generalInformationsStatistics();
        $certificates = EligibleCertificates::count();
        $users = User::count();
        $books = Book::count();
        $auditer = Role::where('name', 'auditer')->count();
        $reviewer = Role::where('name', 'reviewer')->count();



        $response = [
            "thesises" => $thesis,
            "questions" => $questions,
            "general_informations" => $generalInformations,
            "certificates" => $certificates,
            'users' => $users,
            "books" => $books,
            "auditers" => $auditer,
            "reviewer" => $reviewer,
        ];
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }


    public function getUserBookByStatus($user_book_status)
    {
        $user_books = EligibleUserBook::where('status', $user_book_status)->with('user')->with('book')->get();
        return $this->jsonResponseWithoutMessage($user_books, 'data', 200);
    }
}
