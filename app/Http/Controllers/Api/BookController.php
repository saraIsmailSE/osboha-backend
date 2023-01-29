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
use App\Models\Language;
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
        $books = Book::with('section', 'type', 'language')->paginate(9);
        if ($books->isNotEmpty()) {
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
            'level' => 'required',
            'language_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        if (Auth::user()->can('create book')) {
            $book = Book::create($request->all());
            $this->createMedia($request->file('image'), $book->id, 'book');
            return $this->jsonResponseWithoutMessage("Book Created Successfully", 'data', 200);
        } else {
            throw new NotAuthorized;
        }
    }
    /**
     * Find and show an existing book in the system by its id.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function show(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $book = Book::find($request->book_id);
        if ($book) {
            return $this->jsonResponseWithoutMessage(new BookResource($book), 'data', 200);
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
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'book_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        if (Auth::user()->can('delete book')) {
            $book = Book::find($request->book_id);
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
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function bookByType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $books = Book::where('type_id', $request->type_id)->get();
        if ($books->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(BookResource::collection($books), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    /**
     * Find and return all books related to a level using level_id.
     *
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function bookByLevel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'level' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $books = Book::where('level', $request->level)->paginate(9);

        // $paginatedCollection = $books;
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
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function bookBySection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section_id' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $books = Book::where('section_id', $request->section_id)->get();
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
        $books = Book::where('name', 'LIKE', '%' . $request->name . '%')->paginate(9);
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
     * @param  Request  $request
     * @return jsonResponseWithoutMessage;
     */
    public function bookByLanguage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'language' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $language_id = Language::where('language', $request->language)->pluck('id')->first();

        $books = Book::where('language_id', $language_id)->paginate(9);
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

    public function getRecentAddedBooks()
    {
        $books = Book::orderBy('created_at', 'desc')->take(9)->get();
        if ($books->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(BookResource::collection($books), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    public function getMostReadableBooks()
    {
        $books = Book::select('books.*', DB::raw('count(*) as total'))
            ->join('theses', 'books.id', '=', 'theses.book_id')                
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
        $books = Book::all();
        $randomBook = $books->random();
        if ($randomBook) {
            return $this->jsonResponseWithoutMessage(new BookResource($randomBook), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
}
