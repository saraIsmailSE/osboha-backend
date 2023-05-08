<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Traits\ResponseJson;
use App\Models\Media;
use App\Traits\MediaTraits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\NotAuthorized;
use App\Exceptions\NotFound;
use App\Http\Resources\BookResource;
use App\Http\Resources\ThesisResource;
use App\Models\BookLevel;
use App\Models\Language;
use App\Models\PostType;
use App\Models\TimelineType;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    use ResponseJson, MediaTraits;

    /**
     *Read all information about all books in the system
     * 
     * @return jsonResponseWithoutMessage;
     */
    public function index()
    {
        $books = Book::with(['userBooks' => function ($query) {
            $query->where('user_id', Auth::user()->id);
        }])->paginate(9);
        if ($books->isNotEmpty()) {

            foreach ($books as $book) {
                $book->last_thesis = Auth::user()
                    ->theses()
                    ->where('book_id', $book->id)
                    ->orderBy('end_page', 'desc')
                    ->orderBy('updated_at', 'desc')->first();
            }

            return $this->jsonResponseWithoutMessage([
                'books' => BookResource::collection($books),
                'total' => $books->total(),
            ], 'data', 200);
        } else {
            throw new NotFound;
        }
    }

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
            'writer' => 'required',
            'publisher' => 'required',
            'brief' => 'required',
            'start_page' => 'required',
            'end_page' => 'required',
            'link' => 'required',
            'section_id' => 'required',
            'type_id' => 'required',
            'image' => 'required',
            'level_id' => 'required',
            'language_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (Auth::user()->can('create book')) {
            $book = Book::create($request->all());

            if (!$book) {
                return $this->jsonResponseWithoutMessage("Book Not Created", 'data', 500);
            }

            $media = $this->createMedia($request->file('image'), $book->id, 'book');

            if (!$media) {
                //delete the created book
                $book->delete();
                return $this->jsonResponseWithoutMessage("Book Not Created", 'data', 500);
            }

            //create post for book
            $post = $book->posts()->create([
                'user_id' => Auth::user()->id,
                'body' => $request->brief,
                'type_id' => PostType::where('type', 'book')->first()->id,
                'timeline_id' => TimelineType::where('type', 'book')->first()->id,
            ]);

            if (!$post) {
                //delete the created book
                $book->delete();
                $this->deleteMedia($media->id);
                return $this->jsonResponseWithoutMessage("Book Not Created", 'data', 500);
            }

            return $this->jsonResponseWithoutMessage("Book Created Successfully", 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }
    /**
     * Find and show an existing book in the system by its id.
     *
     * @param  Int  $book_id
     * @return jsonResponse
     */
    public function show($book_id)
    {
        $book = Book::where('id', $book_id)->with('userBooks', function ($query) {
            $query->where('user_id', Auth::user()->id);
        })->first();

        if ($book) {
            $book_post = $book->posts->where('type_id', PostType::where('type', 'book')->first()->id)->first();
            //calculate book rate percentage
            $rate_sum = $book_post->rates->sum('rate');
            $rate_total = $book_post->rates->count();
            $rate = $rate_total > 0 ? (($rate_sum / $rate_total) / 5) * 100  : 0;

            //comments count
            $comments_count = $book_post->comments ? $book_post->comments->count() : 0;

            //get last added thesis on this book by this user with greatest end page
            $last_thesis = Auth::user()
                ->theses()
                ->where('book_id', $book_id)

                ->orderBy('end_page', 'desc')
                ->orderBy('updated_at', 'desc')->first();

            return $this->jsonResponseWithoutMessage(
                [
                    'book' => new BookResource($book),
                    'theses_count' => $book->theses->count(),
                    'comments_count' => $comments_count,
                    'rate' => $rate,
                    'last_thesis' => $last_thesis ? new ThesisResource($last_thesis) : null,
                ],
                'data',
                200
            );
        } else {
            throw new NotFound;
        }
    }

    /**
     * Update an existing book’s details ( “edit book” permission is required).
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (Auth::user()->can('edit book')) {
            $book = Book::find($request->book_id);
            if ($book) {
                if ($request->hasFile('image')) {
                    $currentMedia = Media::where('book_id', $book->id)->first();
                    // if exists, update
                    if ($currentMedia) {
                        $this->updateMedia($request->file('image'), $currentMedia->id);
                    }
                    //else create new one
                    else {
                        // upload media
                        $this->createMedia($request->file('image'), $book->id, 'book');
                    }
                }
                $book->update($request->all());
                return $this->jsonResponseWithoutMessage("Book Updated Successfully", 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }
    /**
     * Delete an existing book's in the system using its id (“delete book” permission is required). 
     *
     * @param  Int  $book_id
     * @return jsonResponseWithoutMessage;
     */
    public function delete($book_id)
    {
        if (Auth::user()->can('delete book')) {
            $book = Book::find($book_id);
            if ($book) {
                //check Media
                $currentMedia = Media::where('book_id', $book->id)->first();
                // if exist, delete
                if ($currentMedia) {
                    $this->deleteMedia($currentMedia->id);
                }
                $book->delete();
                return $this->jsonResponseWithoutMessage("Book Deleted Successfully", 'data', 200);
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotAuthorized;
        }
    }

    /**
     * Find and return all books related to a type using type_id.
     *
     * @param  Int $type_id
     * @return jsonResponseWithoutMessage;
     */
    public function bookByType($type_id)
    {
        $books = Book::where('type_id', $type_id)->with(['userBooks' => function ($query) {
            $query->where('user_id', Auth::user()->id);
        }])
        ->get();
        if ($books->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(BookResource::collection($books), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    /**
     * Find and return all books related to a level using level.
     *
     * @param  String $level
     * @return jsonResponseWithoutMessage;
     */
    public function bookByLevel($level)
    {
        $level_id = BookLevel::where('level', $level)->orWhere('arabic_level', $level)->pluck('id')->first();

        if (!$level_id) {
            throw new NotFound;
        }

        $books = Book::where('level_id', $level_id)->with(['userBooks' => function ($query) {
            $query->where('user_id', Auth::user()->id);
        }])
        ->paginate(9);

        if ($books->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(
                [
                    'books' => BookResource::collection($books),
                    'total' => $books->total(),
                ],
                'data',
                200
            );
        } else {
            throw new NotFound;
        }
    }

    /**
     * Find and return all books related to a section using section_id.
     *
     * @param  Int $section_id
     * @return jsonResponseWithoutMessage;
     */
    public function bookBySection($section_id)
    {
        $books = Book::where('section_id', $section_id)->get();
        if ($books->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(BookResource::collection($books), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    /**
     * Find and return all books related to name letters using name_id.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function bookByName(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $books = Book::where('name', 'LIKE', '%' . $request->name . '%')
        ->with(['userBooks' => function ($query) {
            $query->where('user_id', Auth::user()->id);
        }])
        ->paginate(9);
        if ($books->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(
                [
                    'books' => BookResource::collection($books),
                    'total' => $books->total(),
                ],
                'data',
                200
            );
        } else {
            throw new NotFound;
        }
    }

    /**
     * Find and return all books related to language language_id
     *
     * @param  String $language
     * @return jsonResponseWithoutMessage;
     */
    public function bookByLanguage($language)
    {
        $language_id = Language::where('language', $language)->pluck('id')->first();

        if ($language_id) {
            $books = Book::where('language_id', $language_id)
            ->with(['userBooks' => function ($query) {
                $query->where('user_id', Auth::user()->id);
            }])
            ->paginate(9);
            if ($books->isNotEmpty()) {
                return $this->jsonResponseWithoutMessage(
                    [
                        'books' => BookResource::collection($books),
                        'total' => $books->total(),
                    ],
                    'data',
                    200
                );
            } else {
                throw new NotFound;
            }
        } else {
            throw new NotFound;
        }
    }

    public function getRecentAddedBooks()
    {
        $books = Book::with(['userBooks' => function ($query) {
            $query->where('user_id', Auth::user()->id);
        }])
        ->orderBy('created_at', 'desc')->take(9)->get();
        if ($books->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(BookResource::collection($books), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    public function getMostReadableBooks()
    {
        $books = Book::select('books.*', DB::raw('count(*) as total'))
            ->with('theses')
            ->with(['userBooks' => function ($query) {
                $query->where('user_id', Auth::user()->id);
            }])    
            ->groupBy('book_id')
            ->groupBy('user_id')
            ->orderBy('total', 'desc')
            ->take(9)
            ->get();

        if ($books->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(BookResource::collection($books), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    public function getRandomBook()
    {
        $books = Book::with(['userBooks' => function ($query) {
            $query->where('user_id', Auth::user()->id);
        }])    
        ->get();
        $randomBook = $books->random();
        if ($randomBook) {
            return $this->jsonResponseWithoutMessage(new BookResource($randomBook), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    public function latest()
    {
        $books = Book::latest()->first();
        if ($books) {
            return $this->jsonResponseWithoutMessage($books, 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}