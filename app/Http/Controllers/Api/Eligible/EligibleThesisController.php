<?php

namespace App\Http\Controllers\Api\Eligible;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EligibleGeneralInformations;
use App\Models\EligibleQuestion;
use App\Models\EligibleThesis;
use App\Models\User;
use App\Models\EligibleUserBook;
use Illuminate\Support\Facades\Validator;
use App\Traits\ResponseJson;
use Illuminate\Support\Facades\Auth;


class EligibleThesisController extends Controller
{
    use ResponseJson;


    public function index()
    {

        $thesis = EligibleThesis::all();
        return $this->jsonResponseWithoutMessage($thesis, 'data', 200);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "thesis_text" => "required",
            "ending_page" => 'required',
            "starting_page" => 'required',
            "eligible_user_books_id" => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $input = $request->all();

        try {
            $newthesis = EligibleThesis::create($input);
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->sendError($e, 'User Book does not exist');
        }
        $thesis = EligibleThesis::find($newthesis->id);
        return $this->jsonResponseWithoutMessage($thesis, 'data', 200);
    }


    public function show($id)
    {
        $thesis = EligibleThesis::where('id', $id)->with('user_book.book')->first();

        if (is_null($thesis)) {

            return $this->sendError('Thesis does not exist');
        }
        return $this->jsonResponseWithoutMessage($thesis, 'data', 200);
    }

    public function update(Request $request,  $id)
    {
        $validator = Validator::make($request->all(), [
            "text" => "required",
            "ending_page" => 'required',
            "starting_page" => 'required',
        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }


        try {
            $thesis = EligibleThesis::find($id);
            if (Auth::id() == $thesis->user_book->user_id) {

                $thesis->thesis_text = $request->text;
                $thesis->ending_page = $request->ending_page;
                $thesis->starting_page = $request->starting_page;
                $thesis->save();
            }
        } catch (\Error $e) {
            return $this->sendError('Thesis does not exist');
        }
        return $this->jsonResponseWithoutMessage($thesis, 'data', 200);
    }

    public function destroy($id)
    {

        $thesis = EligibleThesis::destroy($id);

        if ($thesis == 0) {

            return $this->sendError('thesis not found!');
        }
        return $this->jsonResponseWithoutMessage($thesis, 'data', 200);
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

        $thesis = EligibleThesis::find($id);
        $thesis->reviews = $request->reviews;
        $thesis->degree = $request->degree;
        $thesis->auditor_id = $request->auditor_id;
        $thesis->status = 'audited';


        try {
            $thesis->save();
            // Stage Up
            $auditedTheses = EligibleThesis::where('eligible_user_books_id', $thesis->eligible_user_books_id)->where('status', 'audited')->count();
            $auditedGeneralInfo = EligibleGeneralInformations::where('eligible_user_books_id', $thesis->eligible_user_books_id)->where('status', 'audited')->count();
            $auditedQuestions = EligibleQuestion::where('eligible_user_books_id', $thesis->eligible_user_books_id)->where('status', 'audited')->count();
            if ($auditedTheses >= 8 && $auditedQuestions >= 5 && $auditedGeneralInfo) {
                $userBook = EligibleUserBook::where('id', $thesis->eligible_user_books_id)->update(['status' => 'audited']);
            }
        } catch (\Error $e) {
            return $this->sendError('Thesis does not exist');
        }
        return $this->jsonResponseWithoutMessage($thesis, 'data', 200);
    }




    public function finalDegree($eligible_user_books_id)
    {
        $degrees = EligibleThesis::where("eligible_user_books_id", $eligible_user_books_id)->avg('degree');
        return $this->jsonResponseWithoutMessage('Final Degree!', $degrees, 200);
    }

    //ready to review

    public function reviewThesis($id)
    {
        try {
            $thesis = EligibleThesis::where('eligible_user_books_id', $id)->where(function ($query) {
                $query->where('status', 'retard')
                    ->orWhereNull('status');
            })->update(['status' => 'ready']);
            return $thesis;
        } catch (\Error $e) {
            return $this->sendError('Thesis does not exist');
        }
    }

    public function review(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required_without:eligible_user_books_id',
            'eligible_user_books_id' => 'required_without:id',
            'status' => 'required',
            'reviewer_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        try {
            if ($request->has('id')) {
                $thesis = EligibleThesis::find($request->id);
                $thesis->status = $request->status;
                $thesis->reviewer_id = $request->reviewer_id;
                if ($request->has('reviews')) {
                    $thesis->reviews = $request->reviews;
                    $userBook = EligibleUserBook::find($thesis->eligible_user_books_id);
                    $user = User::find($userBook->user_id);
                    $userBook->status = $request->status;
                    $userBook->reviews = $request->reviews;
                    $userBook->save();
                    $user->notify(
                        (new \App\Notifications\RejectAchievement($userBook->book->name))->delay(now()->addMinutes(2))
                    );
                }
                $thesis->save();
            } else if ($request->has('eligible_user_books_id')) {
                $thesis = EligibleThesis::where('eligible_user_books_id', $request->eligible_user_books_id)->where('status', 'accept')->update(['status' => $request->status]);
            }
        } catch (\Error $e) {
            return $this->sendError('Thesis does not exist');
        }
    }

    public function getByStatus($status)
    {
        $theses =  EligibleThesis::with("user_book")
            ->where('status', $status)
            ->groupBy('eligible_user_books_id')
            ->orderBy('updated_at', 'asc')
            ->get();

        return $this->jsonResponseWithoutMessage($theses, 'data', 200);
    }

    public function getByUserBook($eligible_user_books_id, $status = '')
    {
        if ($status != '') {
            $response['thesises'] =  EligibleThesis::with("user_book.user")->with("user_book.book")->with('reviewer')->with('auditor')->where('eligible_user_books_id', $eligible_user_books_id)->where('status', $status)->get();
        } else {
            $response['thesises'] =  EligibleThesis::with("user_book.user")->with("user_book.book")->with('reviewer')->with('auditor')->where('eligible_user_books_id', $eligible_user_books_id)->get();
        }
        $response['acceptedThesises'] =  EligibleThesis::where('eligible_user_books_id', $eligible_user_books_id)->where('status', 'accept')->count();
        $response['userBook'] =  EligibleUserBook::find($eligible_user_books_id);
        return $this->jsonResponseWithoutMessage($response, 'data', 200);
    }

    public function getByBook($book_id)
    {
        $theses['user_book'] = EligibleUserBook::where('user_id', Auth::id())->where('book_id', $book_id)->first();
        $theses['theses'] =  EligibleThesis::with('reviewer')->with('auditor')->where('eligible_user_books_id', $theses['user_book']->id)->orderBy('created_at')->get();
        return $this->jsonResponseWithoutMessage($theses, 'data', 200);
    }


    public static function thesisStatistics()
    {
        $thesisCount = EligibleThesis::count();
        $very_excellent =  EligibleThesis::where('degree', '>=', 95)->where('degree', '<', 100)->count();
        $excellent = EligibleThesis::where('degree', '>', 94.9)->where('degree', '<', 95)->count();
        $veryGood =  EligibleThesis::where('degree', '>', 89.9)->where('degree', '<', 85)->count();
        $good = EligibleThesis::where('degree', '>', 84.9)->where('degree', '<', 80)->count();
        $accebtable = EligibleThesis::where('degree', '>', 79.9)->where('degree', '<', 70)->count();
        $rejected = EligibleThesis::where('status', 'rejected')->count();
        return [
            "total" => $thesisCount,
            "very_excellent" => ($very_excellent / $thesisCount) * 100,
            "excellent" => ($excellent / $thesisCount) * 100,
            "very_good" => ($veryGood / $thesisCount) * 100,
            "good" => ($good / $thesisCount) * 100,
            "accebtable" => ($accebtable / $thesisCount) * 100,
            "rejected" => ($rejected / $thesisCount) * 100,
        ];
    }

    public static function thesisStatisticsForUser($id)
    {
        $thesisCount = EligibleUserBook::join('thesis', 'eligible_user_books.id', '=', 'thesis.eligible_user_books_id')->where('user_id', $id)->count();
        $very_excellent =  EligibleUserBook::join('thesis', 'eligible_user_books .id', '=', 'thesis.eligible_user_books_id')->where('user_id', $id)->where('degree', '<=', 100)->count();
        $excellent = EligibleUserBook::join('thesis', 'eligible_user_books .id', '=', 'thesis.eligible_user_books_id')->where('user_id', $id)->where('degree', '<', 95)->count();
        $veryGood =  EligibleUserBook::join('thesis', 'eligible_user_books .id', '=', 'thesis.eligible_user_books_id')->where('user_id', $id)->where('degree', '<', 85)->count();
        $good = EligibleUserBook::join('thesis', 'eligible_user_books .id', '=', 'thesis.eligible_user_books_id')->where('user_id', $id)->where('degree', '<', 80)->count();
        $accebtable = EligibleUserBook::join('thesis', 'eligible_user_books .id', '=', 'thesis.eligible_user_books_id')->where('user_id', $id)->where('degree', '<', 70)->count();
        return [
            "total" => $thesisCount,
            "very_excellent" => ($very_excellent / $thesisCount) * 100,
            "excellent" => ($excellent / $thesisCount) * 100,
            "very_good" => ($veryGood / $thesisCount) * 100,
            "good" => ($good / $thesisCount) * 100,
            "accebtable" => ($accebtable / $thesisCount) * 100
        ];
    }
}
