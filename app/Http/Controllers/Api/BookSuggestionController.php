<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResponseJson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\NotAuthorized;
use App\Models\BookSuggestion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BookSuggestionController extends Controller
{
    use ResponseJson;

    /**
     *Add a new book to the system (“create book” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponse;
     */
    public function create(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'publisher' =>  'required',
            'brief' =>  'required',
            'section_id' =>  'required',
            'language_id' =>  'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        try {

            $book = new BookSuggestion();

            $book->user_id = Auth::id();
            $book->name = $request->name;
            $book->publisher = $request->publisher;

            $book->brief = $request->brief;

            $book->section_id = $request->section_id;
            $book->language_id = $request->language_id;

            //link
            if ($request->link) {
                $book->link = $request->link;
            }
            $book->save();

            return $this->jsonResponseWithoutMessage($book, 'data', 200);
        } catch (\Exception $e) {
            Log::channel('books')->info($e);
            return $this->jsonResponseWithoutMessage($e->getMessage() . ' at line ' . $e->getLine(), 'data', 500);
        }
    }
    /**
     * Find and show an existing book in the system by its id.
     *
     * @param  Int  $book_id
     * @return jsonResponse
     */
    public function show($suggestion_id)
    {
        $book = BookSuggestion::with(['reviewer', 'user', 'section', 'language',])->find($suggestion_id);
        return $this->jsonResponseWithoutMessage($book, 'data', 200);
    }
    public function listByStatus($status)
    {
        if (!Auth::user()->hasanyrole('admin|book_quality_team_coordinator|book_quality_team')) {
            throw new NotAuthorized;
        }

        $books = BookSuggestion::with(['user', 'section', 'language', 'reviewer'])->where('status', $status)->paginate(30);
        if ($books->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage([
                'books' => $books,
                'total' => $books->total(),
                'last_page' => $books->lastPage(),
                'per_page' => $books->perPage(),
                'current_page' => $books->currentPage(),
            ], 'data', 200);
        }

        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }

    public function updateStatus(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'status' => 'required',
            'suggestion_id' => 'required',
            'reviewer_note' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (!Auth::user()->hasanyrole('admin|book_quality_team_coordinator|book_quality_team')) {
            throw new NotAuthorized;
        }

        $suggestionRecored = BookSuggestion::find($request->suggestion_id);
        $suggestionRecored->status = $request->status;
        $suggestionRecored->reviewer_id = Auth::id();
        $suggestionRecored->reviewer_note = $request->reviewer_note;
        $suggestionRecored->save();
        $suggestionRecored->refresh();

        return $this->jsonResponseWithoutMessage($suggestionRecored, 'data', 200);
    }

    public function isAllowedToSuggest()
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $bookSuggestionsCount = BookSuggestion::where('user_id', Auth::id())
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        return $this->jsonResponseWithoutMessage($bookSuggestionsCount, 'data', 200);
    }
}
