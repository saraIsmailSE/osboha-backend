<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\BookResource;
use App\Http\Resources\UserExceptionResource;
use App\Models\Book;
use App\Models\BookType;
use App\Models\Mark;
use App\Models\Thesis;
use App\Models\User;
use App\Models\UserBook;
use App\Models\Week;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserBookController extends Controller
{
    use ResponseJson, MediaTraits;
    /**
     * Find [in progress - finished ] books belongs to specific user.
     *
     * @param user_id
     * @return jsonResponse[user books]
     */
    public function show($user_id)
    {
        $books = UserBook::where(function ($query) {
            $query->Where('status', 'in progress')->orWhere('status', 'finished');
        })->where('user_id', $user_id)->get();

        return $this->jsonResponseWithoutMessage($books, 'data', 200);
    }


    /**
     * Find later books belongs to specific user.
     *
     * @param user_id
     * @return jsonResponse[user books]
     */
    public function later($user_id)
    {
        $books = UserBook::where('status', 'later')->where('user_id', $user_id)->get();
        return $this->jsonResponseWithoutMessage($books, 'data', 200);
    }



    /**
     * Find free books belongs to specific user.
     *
     * @param user_id , page for 
     * @return jsonResponse[user books]
     */
    public function free($user_id, $page)
    {
        $free_book = BookType::where('type', 'free')->first();
        $in_progress_books = 0;

        $can_add_books = true;
        $user = User::find($user_id);

        $userFreeBooks = UserBook::with('book')->where('user_id', $user_id)->whereHas('book.type', function ($q) {
            $q->where('type', '=', 'free');
        })->paginate(9);

        if ($userFreeBooks->isNotEmpty()) {
            $books = collect();
            foreach ($userFreeBooks as $userFreeBook) {
                $userFreeBook->book->last_thesis = $user
                    ->theses()
                    ->where('book_id', $userFreeBook->book->id)
                    ->orderBy('end_page', 'desc')
                    ->orderBy('updated_at', 'desc')->first();
                $books->push($userFreeBook->book);

                if ($userFreeBook->status == 'in progress') {
                    $in_progress_books++;
                }
            }

            if (($in_progress_books < 3 || $in_progress_books == 0) && $user_id == Auth::id()) {
                $can_add_books = true;
            } else {
                $can_add_books = false;
            }
            return $this->jsonResponseWithoutMessage([
                'books' => BookResource::collection($books),
                'total' => $books->count(),
                'in_progress_books' => $in_progress_books,
                'can_add_books' => $can_add_books,
            ], 'data', 200);
        } else {
            return $this->jsonResponseWithoutMessage([
                'in_progress_books' => $in_progress_books,
                'can_add_books' => $can_add_books,
            ], 'data', 200);
        }
    }

    /**
     * Check rules for free book.
     *
     * @param user_id
     * @return jsonResponse[eligible_to_write_thesis boolean]
     */
    public function eligibleToWriteThesis($user_id)
    {


        if (Auth::user()->hasRole('book_quality_team')) {

            return $this->jsonResponseWithoutMessage(true, 'data', 200);
        } else {

            // two books finished
            $userNotFreeBooks_finished = UserBook::where('user_id', $user_id)->where('status', 'finished')->whereHas('book.type', function ($q) {
                $q->where('type', '=', 'normal');
            })->get();

            $userFreeBooks = UserBook::where('user_id', $user_id)->whereHas('book.type', function ($q) {
                $q->where('type', '=', 'free');
            })->get();

            if ($userNotFreeBooks_finished->isNotEmpty() && $userFreeBooks->count() / $userNotFreeBooks_finished->count() == 0.5) {
                return $this->jsonResponseWithoutMessage(true, 'data', 200);
            }
            // no finished books => check for one in progress have at least 1 thesis for this week
            else {
                $userNotFreeBooks_notFinished = UserBook::where('user_id', $user_id)->where('status', 'in progress')->whereHas('book.type', function ($q) {
                    $q->where('type', '=', 'normal');
                })->get();

                if ($userNotFreeBooks_notFinished->isNotEmpty()) {
                    /* Check Theses
                        * at least 18 pages 
                        * at least full thesis [length=> at least 400 letters] or [at least 3 screenshots or 3 theses]
    
                        */

                    //current week id [ to check this week theses]
                    $current_week = Week::latest()->pluck('id')->first();

                    $thisWeekMark = Mark::where('user_id', $user_id)
                        ->where('week_id', $current_week)
                        ->where('total_pages', '>=', 18)
                        ->first();

                    if ($thisWeekMark) {

                        //  at least 3 screenshots or 3 theses
                        if ($thisWeekMark->total_screenshot >= 3 || $thisWeekMark->total_thesis >= 3) {
                            return $this->jsonResponseWithoutMessage(true, 'data', 200);
                        }
                        // at least full thesis [all thesis length >= 400 ]
                        else {
                            $theses_max_length =  Thesis::where('mark_id', $thisWeekMark->id)
                                ->select(
                                    DB::raw('sum(max_length) as max_length'),
                                )->first()->max_length;

                            if ($theses_max_length >= 400) {
                                return $this->jsonResponseWithoutMessage(true, 'data', 200);
                            }
                        }
                    } else {
                        return $this->jsonResponseWithoutMessage(false, 'data', 200);
                    }
                } else {
                    return $this->jsonResponseWithoutMessage(false, 'data', 200);
                }
            }
            //have theses from one book this week
        }
    }


    /**
     * Update an existing book belongs to user .
     * 
     *  @param  Request  $request
     * @return jsonResponseWithoutMessage
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'book_id' => 'required',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        try {
            $user_book = UserBook::where('user_id', $request->user_id)
                ->where('book_id', $request->book_id)
                ->update(['status' => $request->status]);
            return $this->jsonResponse($user_book, 'data', 200, 'updated successfully');
        } catch (\Illuminate\Database\QueryException $error) {
            return $this->jsonResponseWithoutMessage($error, 'data', 500);
        }
    }

    public function startBookAgain($book_id)
    {
        $user_book = UserBook::where('user_id', Auth::user()->id)
            ->where('book_id', $book_id)->first();

        if (!$user_book) {
            return $this->jsonResponseWithoutMessage('book not found', 'data', 404);
        }

        $user_book->status = 'in progress';
        $user_book->counter = $user_book->counter + 1;
        $user_book->save();
        return $this->jsonResponse($user_book, 'data', 200, 'updated successfully');
    }

    public function saveBookForLater($id)
    {
        $book = Book::find($id);

        if (!$book) {
            throw new NotFound;
        }

        $userBook = UserBook::where('user_id', Auth::id())->where('book_id', $id)->first();

        if ($userBook) {
            if ($userBook->status === 'later') {
                $userBook->delete();
                return $this->jsonResponse(null, 'data', 200, 'تم حذف الكتاب من المحفوظات');
            } else if ($userBook->status === 'finished') {
                return $this->jsonResponse($userBook, 'data', 200, 'لقد قرأت هذا الكتاب من قبل, بإمكانك أن تجده في قائمة كتبك');
            } else {
                return $this->jsonResponse($userBook, 'data', 200, 'أنت حالياً تقرأ في هذا الكتاب, بإمكانك أن تجده في قائمة كتبك');
            }
        } else {
            $userBook = UserBook::create([
                'user_id' => Auth::id(),
                'book_id' => $id,
                'status' => 'later',
            ]);
            $userBook->fresh();
            return $this->jsonResponse($userBook, 'data', 200, 'تم حفظ الكتاب في قائمة المحفوظات');
        }
    }

    public function deleteForLater($id)
    {
        UserBook::where('id', $id)->where('user_id', Auth::id())->delete();
        $books = UserBook::where('status', 'later')->where('user_id', Auth::id())->get();
        return $this->jsonResponseWithoutMessage($books, 'data', 200);
    }
}
