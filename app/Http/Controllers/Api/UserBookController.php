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
use App\Traits\ThesisTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserBookController extends Controller
{
    use ResponseJson, MediaTraits, ThesisTraits;
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
    public function free($user_id)
    {
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

                //if the book is finished before (counter > 0), return the theses from the last userBook update
                $userTheses = $user->theses()->where('book_id', $userFreeBook->book->id);
                if ($userFreeBook->counter > 0) {
                    $timestamp = $userFreeBook->finished_at ?? $userFreeBook->updated_at;
                    $userTheses = $userTheses->where('created_at', '>', $timestamp);
                }

                $finishedPercentage = $this->calculateBookPagesPercentage(null, $userFreeBook->book, $userTheses->get());
                $userFreeBook->book->finished_percentage = min(round($finishedPercentage, 2), 100);

                $userFreeBook->book->load(['userBooks' => function ($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                }]);

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
                'total' => $userFreeBooks->total(),
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
     * Find normal and ramadan books belongs to specific user.
     *
     * @param user_id , page for
     * @return jsonResponse[user books]
     */
    public function osbohaUserBook($user_id, $name = '')
    {

        $userBooks = UserBook::with('book')->where('user_id', $user_id)->where(function ($query) {
            $query->Where('status', 'in progress')->orWhere('status', 'finished');
        })->whereHas('book.type', function ($q) {
            $q->where('type', '=', 'normal')->orWhere('type', '=', 'ramadan');
        })->whereHas('book', function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%');
        })->paginate(9);

        if ($userBooks->isNotEmpty()) {
            $books = collect();
            $user = User::find($user_id);

            foreach ($userBooks as $userBook) {
                $userBook->book->last_thesis =
                    $user
                    ->theses()
                    ->where('book_id', $userBook->book->id)
                    ->orderBy('end_page', 'desc')
                    ->orderBy('updated_at', 'desc')->first();

                //if the book is finished before (counter > 0), return the theses from the last userBook update
                $userTheses = $user->theses()->where('book_id', $userBook->book->id);
                if ($userBook->counter > 0) {
                    $timestamp = $userBook->finished_at ?? $userBook->updated_at;
                    $userTheses = $userTheses->where('created_at', '>', $timestamp);
                }

                $finishedPercentage = $this->calculateBookPagesPercentage(null, $userBook->book, $userTheses->get());
                $userBook->book->finished_percentage = min(round($finishedPercentage, 2), 100);

                $userBook->book->load(['userBooks' => function ($query) use ($user_id) {
                    $query->where('user_id', $user_id);
                }]);
                $books->push($userBook->book);
            }

            return $this->jsonResponseWithoutMessage([
                'books' => BookResource::collection($books),
                'total' => $userBooks->total(),
            ], 'data', 200);
        }
        return $this->jsonResponseWithoutMessage(null, 'data', 200);
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
                $q->where('type', '=', 'normal')->orWhere('type', '=', 'ramadan');
            })->get();

            $userFreeBooks = UserBook::where('user_id', $user_id)->where('status', 'finished')->whereHas('book.type', function ($q) {
                $q->where('type', '=', 'free');
            })->get();


            //UserNotFreeBooks >= UserFreeBooks *2 +2
            if ($userNotFreeBooks_finished->isNotEmpty() && ($userNotFreeBooks_finished->count() >= ($userFreeBooks->count() * 2 + 2))) {
                return $this->jsonResponseWithoutMessage(true, 'data', 200);
            }
            // no finished books => check for thesis in this week
            else {
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

    function bookQualityUsersStatics($week_id = 0)
    {

        if (Auth::user()->hasanyrole(['admin', 'book_quality_team'])) {

            if ($week_id == 0) {
                // not specified week => get previous week
                $week_id = Week::orderBy('created_at', 'desc')->skip(1)->take(2)->pluck('id')->first();
            } else {
                $week_id = Week::where('id', $week_id)->pluck('id')->firstOrFail();
            }

            $book_quality_users_statics = [];
            $book_quality_users = User::without('userProfile')->role('book_quality_team')->get();

            $book_quality_users_mark = Mark::without('week', 'user')->whereIn('user_id', $book_quality_users->pluck('id'))
                ->where('week_id', $week_id)
                ->with('thesis.book') // Eager load thesis and book relationships
                ->get();

            foreach ($book_quality_users as $user) {
                $mark = $book_quality_users_mark->firstWhere('user_id', $user->id);

                if ($mark) { // Ensure $mark is not null
                    if ($mark->is_freezed != 0) {
                        $book_quality_users_statics[$user->name]['status'] = 'السفير بحالة تجميد';
                    } else {
                        $theses = $mark->thesis;

                        // Group the thesis data by book_name and calculate the total pages read for each book
                        $groupedByBookName = $theses->groupBy(function ($thesis) {
                            return $thesis->book->name; // Group by book name
                        });

                        $totalPagesByBookName = $groupedByBookName->map(function ($thesisGroup, $bookName) {
                            return $thesisGroup->sum(function ($thesis) {
                                return $thesis->end_page - $thesis->start_page + 1; // Calculate total pages read
                            });
                        });
                        $book_quality_users_statics[$user->name]['status'] = 'active';
                        $book_quality_users_statics[$user->name]['statics'] = [
                            'total_pages' => $mark->total_pages,
                            'total_thesis' => $mark->total_thesis,
                            'total_screenshot' => $mark->total_screenshot,
                            'book' => $totalPagesByBookName
                        ];
                    }
                } else {
                    $book_quality_users_statics[$user->name]['status'] = 'لا يوجد انجاز لهذا السفير';
                }
            }
            return $this->jsonResponseWithoutMessage($book_quality_users_statics, 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }

    public function markBookAsFinished(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required|exists:books,id',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors()->first(), 'data', 500);
        }

        $userBook = UserBook::where('user_id', Auth::id())
            ->where('book_id', $request->book_id)
            ->firstOrFail();

        if ($userBook->status == 'finished') {
            return $this->jsonResponseWithoutMessage('الكتاب مكتمل بالفعل', 'data', 200);
        }

        $userBook->status = 'finished';
        $userBook->counter = $userBook->counter + 1;
        $userBook->finished_at = now();
        $userBook->save();
        return $this->jsonResponseWithoutMessage('تم الانتهاء من الكتاب', 'data', 200);
    }
}
