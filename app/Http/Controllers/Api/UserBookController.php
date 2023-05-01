<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\UserExceptionResource;
use App\Models\Book;
use App\Models\User;
use App\Models\UserBook;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Traits\ResponseJson;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;

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
}