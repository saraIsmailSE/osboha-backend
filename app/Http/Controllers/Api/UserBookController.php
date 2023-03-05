<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Exceptions\NotFound;
use App\Exceptions\NotAuthorized;
use App\Http\Resources\UserExceptionResource;
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
     * Find all books belongs to specific user.
     *
     * @param user_id
     * @return jsonResponse[user books]
     */
    public function show($user_id)
    {
        $books = UserBook::where(function ($query){
            $query->Where('status', 'in progress')->orWhere('status', 'finished');
        })->where('user_id', $user_id)->get();

        if ($books->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage($books, 'data', 200);
        } else {
            throw new NotFound;
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

        $user_book = UserBook::find($request->user_profile_id);
        try {
            $user_book=UserBook::where('user_id', $request->user_id)
            ->where('book_id', $request->book_id)
            ->update(['status' => $request->status]);
            return $this->jsonResponse($user_book, 'data', 200,'updated successfully');
        } catch (\Illuminate\Database\QueryException $error) {
            return $this->jsonResponseWithoutMessage($error, 'data', 500);
        }

    }
}
