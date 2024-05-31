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
use App\Models\BookType;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostType;
use App\Models\RamadanDay;
use App\Models\Section;
use App\Models\Thesis;
use App\Models\Timeline;
use App\Models\TimelineType;
use App\Models\UserBook;
use App\Models\ViolatedBook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $books = Book::with('posts')->with('media')->whereHas('type', function ($q) {
            $q->where('type', '=', 'normal')->orWhere('type', '=', 'ramadan');
        })->with(['userBooks' => function ($query) {
            $query->where('user_id', Auth::user()->id);
        }])->where('is_active', 1)->paginate(9);

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

    public function getAllForEligible()
    {
        if (isset($_GET['name'])  && $_GET['name'] != '') {
            $books['books']  = Book::where('is_active', 1)->where('name', 'like', '%' . $_GET['name'] . '%')
                ->whereHas('type', function ($q) {
                    $q->where('type', '=', 'normal');
                })->paginate(9);
        } else {
            $books['books']  = Book::where('is_active', 1)->whereHas('type', function ($q) {
                $q->where('type', '=', 'normal');
            })->paginate(9);
        }

        // SELECT * FROM `user_book` WHERE user_id =1 and (status != 'finished' || status is null )

        $books['open_book'] = Book::whereHas('eligibleUserBook', function ($q) {
            $q->where('user_id', Auth::id())
                ->where(function ($query) {
                    $query->where('status', '!=', 'finished')
                        ->where('status', '!=', 'rejected')
                        ->orWhereNull('status');
                });
        })->get();

        return $this->jsonResponseWithoutMessage($books, "data", '201');
    }

    public function getAllForRamadan()
    {
        $books = Book::with('posts')->with('media')->whereHas('type', function ($q) {
            $q->whereIn('type', ['ramadan', 'tafseer']);
        })->with(['userBooks' => function ($query) {
            $query->where('user_id', Auth::user()->id);
        }])->where('is_active', 1)->paginate(9);
        if ($books->isNotEmpty()) {

            foreach ($books as $book) {
                $book->last_thesis = Auth::user()
                    ->theses()
                    ->where('book_id', $book->id)
                    ->orderBy('end_page', 'desc')
                    ->orderBy('updated_at', 'desc')->first();
            }

            $isRamadanActive = RamadanDay::whereYear('created_at', now()->year)->where('is_active', 1)->exists();
            return $this->jsonResponseWithoutMessage([
                'books' => BookResource::collection($books),
                'total' => $books->total(),
                'isRamadanActive' => $isRamadanActive,
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
            "writer" => Auth::user()->hasanyrole('admin|book_quality_team') ? 'required' : '',
            'publisher' => Auth::user()->hasanyrole('admin|book_quality_team') ? 'required' : '',
            'link' => Auth::user()->hasanyrole('admin|book_quality_team') ? 'required' : '',
            'brief' => Auth::user()->hasanyrole('admin|book_quality_team') ? 'required' : '',
            'start_page' => 'required',
            "end_page" => 'required',
            'section_id' => Auth::user()->hasanyrole('admin|book_quality_team') ? 'required' : '',
            "level_id" => Auth::user()->hasanyrole('admin|book_quality_team') ? 'required' : '',
            "type_id" => Auth::user()->hasanyrole('admin|book_quality_team') ? 'required' : '',
            'language_id' => Auth::user()->hasanyrole('admin|book_quality_team') ? 'required' : '',
            // "book_media" => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        DB::beginTransaction();
        try {
            $book = new Book();

            $book->name = $request->name;
            $book->start_page = $request->start_page;
            $book->end_page = $request->end_page;

            //writer
            if ($request->writer) {
                $book->writer = $request->writer;
            } else {
                $book->writer = 'غير محدد';
            }

            //publisher
            if ($request->publisher) {
                $book->publisher = $request->publisher;
            } else {
                $book->publisher = 'غير محدد';
            }

            //link
            if ($request->link) {
                $book->link = $request->link;
            } else {
                $book->link = 'https://www.platform.osboha180.com/';
            }

            //brief
            if ($request->brief) {
                $book->brief = $request->brief;
            } else {
                $book->brief = 'لا يوجد وصف';
            }

            //section_id
            if ($request->section_id) {
                $book->section_id = $request->section_id;
            } else {
                $section = Section::where('section', 'غير محدد')->first();
                $book->section_id = $section->id;
            }

            //level_id
            if ($request->level_id) {
                $book->level_id = $request->level_id;
            } else {
                $level = BookLevel::where('arabic_level', 'غير محدد')->first();
                $book->level_id = $level->id;
            }

            //type_id
            if ($request->type_id) {
                $book->type_id = $request->type_id;
            } else {
                $type = BookType::where('type', 'free')->first();
                $book->type_id = $type->id;
            }

            //language_id
            if ($request->language_id) {
                $book->language_id = $request->language_id;
            } else {
                $language = Language::where('language', 'arabic')->first();

                $book->language_id = $language->id;
            }

            if ($request->hasFile('book_media')) {

                //exam_media/user_id/
                $folder_path = 'books';

                // //check if exam_media folder exists
                // if (!file_exists(public_path('assets/images/' . $folder_path))) {
                //     mkdir(public_path('assets/images/' . $folder_path), 0777, true);
                // }

                if ($book->media) {
                    $this->updateMedia($request->book_media, $book->media->id, $folder_path);
                } else {

                    $this->createMedia($request->book_media, $book->id, 'book', $folder_path);
                }
            }
            $book->save();
            if ($book->type->type == 'free') {
                $userBook = UserBook::create([
                    'user_id' => Auth::id(),
                    'book_id' => $book->id,
                    'status' => 'in progress',
                ]);
            }


            //create post for book
            $post = $book->posts()->create([
                'user_id' => Auth::id(),
                'body' => $request->brief,
                'type_id' => PostType::where('type', 'book')->first()->id,
                'timeline_id' => Timeline::where('type_id', TimelineType::where('type', 'book')->first()->id)->first()->id,
            ]);
            DB::commit();

            return $this->jsonResponseWithoutMessage($book, 'data', 200);
        } catch (\Exception $e) {
            Log::channel('books')->info($e);
            DB::rollBack();
            return $this->jsonResponseWithoutMessage($e->getMessage() . ' at line ' . $e->getLine(), 'data', 500);
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
        $book = Book::with('type')->where('id', $book_id)->with('userBooks', function ($query) {
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
                ->orderBy('created_at', 'desc')->first();

            $isRamadanActive = RamadanDay::whereYear('created_at', now()->year)->where('is_active', 1)->exists();

            return $this->jsonResponseWithoutMessage(
                [
                    'book' => new BookResource($book),
                    'book_owner' => $book_post->user_id,
                    'theses_count' => $book->theses->count(),
                    'comments_count' => $comments_count,
                    'rate' => $rate,
                    'last_thesis' => $last_thesis ? new ThesisResource($last_thesis) : null,
                    'isRamadanActive' => $isRamadanActive,
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
            'name' => 'required',
            "writer" => 'required',
            'publisher' => 'required',
            'link' => 'required',
            'brief' => 'required',
            'start_page' => 'required',
            "end_page" => 'required',
            'section_id' => 'required',
            "level_id" => 'required',
            "type_id" => 'required',
            'language_id' => 'required',

        ]);
        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }
        $book = Book::find($request->book_id);
        if ($book) {
            $book_post = $book->posts->where('type_id', PostType::where('type', 'book')->first()->id)->first();

            if (Auth::user()->can('edit book') || $book_post->user_id == Auth::id()) {
                //if there is Media

                if ($request->hasFile('book_media')) {

                    //book//
                    $folder_path = 'books';

                    //check if exam_media folder exists
                    if (!file_exists(public_path('assets/images/' . $folder_path))) {
                        mkdir(public_path('assets/images/' . $folder_path), 0777, true);
                    }

                    if ($book->media) {
                        $this->updateMedia($request->book_media, $book->media->id, $folder_path);
                    } else {

                        $this->createMedia($request->book_media, $book->id, 'book', $folder_path);
                    }
                }
                $book->start_page = $request->start_page;
                $book->name = $request->name;
                $book->section_id =  $request->section_id;
                $book->level_id = $request->level_id;
                $book->end_page = $request->end_page;
                $book->writer  = $request->writer;
                $book->publisher =  $request->publisher;
                $book->link =  $request->link;
                $book->brief =  $request->brief;
                $book->type_id = $request->type_id;
                $book->language_id = $request->language_id;


                $book->save();
                return $this->jsonResponseWithoutMessage("Book Updated Successfully", 'data', 200);
            } else {
                throw new NotAuthorized;
            }
        } else {
            throw new NotFound;
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
        }])->whereHas('type', function ($q) {
            $q->where('type', '=', 'normal')->orWhere('type', '=', 'ramdan');
        })
            ->where('is_active', 1)->get();
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

        $books = Book::where('is_active', 1)->where('level_id', $level_id)->with(['userBooks' => function ($query) {
            $query->where('user_id', Auth::user()->id);
        }])->whereHas('type', function ($q) {
            $q->where('type', '=', 'normal')->orWhere('type', '=', 'ramdan');
        })
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
        $books = Book::whereHas('type', function ($q) {
            $q->where('type', '=', 'normal')->orWhere('type', '=', 'ramdan');
        })->where('section_id', $section_id)->where('is_active', 1)->get();
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
        if (!$request->name) {
            return $this->index();
        }

        $books = Book::where('is_active', 1)->whereHas('type', function ($q) {
            $q->where('type', 'normal')
                ->orWhere('type', 'ramadan');
        })
            ->where('name', 'LIKE', '%' . $request->name . '%')
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
            return $this->jsonResponseWithoutMessage(
                [
                    'books' => [],
                    'total' => 0,
                ],
                'data',
                200
            );
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
            $books = Book::where('is_active', 1)->where('language_id', $language_id)
                ->with(['userBooks' => function ($query) {
                    $query->where('user_id', Auth::user()->id);
                }])->whereHas('type', function ($q) {
                    $q->where('type', '=', 'normal')->orWhere('type', '=', 'ramadan');
                })
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
        }])->whereHas('type', function ($q) {
            $q->where('type', '=', 'normal')->orWhere('type', '=', 'ramadan');
        })
            ->where('is_active', 1)->orderBy('created_at', 'desc')->take(9)->get();
        if ($books->isNotEmpty()) {
            return $this->jsonResponseWithoutMessage(BookResource::collection($books), 'data', 200);
        } else {
            throw new NotFound;
        }
    }

    public function getMostReadableBooks()
    {
        $mostReadableBooks = UserBook::whereHas('book', function ($q) {
            $q->where('is_active', '=', 1);
        })->select(
            DB::raw('book_id, count(book_id) as user_books_count')
        )
            ->where('status', '!=', 'later')
            ->groupBy('book_id')
            ->orderBy('user_books_count', 'DESC')
            ->take(9)
            ->pluck('book_id');
        $books = Book::whereIn('id', $mostReadableBooks)->get();

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
        }])->whereHas('type', function ($q) {
            $q->where('type', '=', 'normal')->orWhere('type', '=', 'ramdan');
        })
            ->where('is_active', 1)->get();
        $randomBook = $books->random();
        if ($randomBook) {
            return $this->jsonResponseWithoutMessage(new BookResource($randomBook), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    public function latest()
    {
        $book = Book::whereHas('type', function ($q) {
            $q->where('type', '=', 'normal')->orWhere('type', '=', 'ramdan');
        })->where('is_active', 1)->latest()->first();
        if ($book) {
            return $this->jsonResponseWithoutMessage(new BookResource($book), 'data', 200);
        } else {
            throw new NotFound;
        }
    }
    public function createReport(Request $request)
    {
        $validatedData = $request->validate([
            'book_id' => 'required|exists:books,id',
            'violation_type' => 'required|string',
            'violated_pages' => 'required|array',
            'violated_pages.*.number' => 'required|integer',
            'description' => 'nullable|string',
            // 'report_media.*' => 'nullable|image|mimes:jpg,jpeg,png,gif,svg',
        ]);

        $violatedPages = serialize($validatedData['violated_pages']);

        $report = ViolatedBook::updateOrCreate(
            [
                'reporter_id' => Auth::id(),
                'book_id' => $validatedData['book_id'],
            ],
            [
                'violation_type' => $validatedData['violation_type'],
                'violated_pages' => $violatedPages,
                'description' => $validatedData['description'],
                'status' => 'pending',
            ]
        );

        // check report_media
        if ($request->has('report_media') && count($request->report_media) > 0) {
            //books/reports-reportID/
            $folder_path = 'books/reports-' . $report->id;
            //delete old media
            $oldMedias = Media::where('book_report_id', $report->id)->get();
            foreach ($oldMedias as $oldMedia) {
                $this->deleteMedia($oldMedia->id);
            }

            $total_report_media = count($request->report_media);
            for ($i = 0; $i < $total_report_media; $i++) {
                $this->createMedia($request->report_media[$i], $report->id, 'book_report', $folder_path);
            }
        }
        return $this->jsonResponseWithoutMessage($report, 'data', 201);
    }

    public function listReportsByStatus($status)
    {
        $reports = ViolatedBook::with('book')->where('status', $status)->paginate(30);
        if ($reports->isNotEmpty()) {
            $reports->each(function ($report) {
                $report->violated_pages = unserialize($report->violated_pages);
            });
            return $this->jsonResponseWithoutMessage([
                'reports' => $reports,
                'total' => $reports->total(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'current_page' => $reports->currentPage(),
            ], 'data', 200);
        }

        return $this->jsonResponseWithoutMessage(null, 'data', 200);
    }
    public function updateReportStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'report_id' => 'required|exists:violated_books,id',
            'status' => 'required|in:pending,rejected,resolved',
            'reviewer_note' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->jsonResponseWithoutMessage($validator->errors(), 'data', 500);
        }

        $report = ViolatedBook::with('book')->findOrFail($request->report_id);

        // Ensure the authenticated user is a reviewer
        if (!Auth::user()->hasanyrole('admin|book_quality_team_coordinator|book_quality_team')) {
            throw new NotAuthorized;
        }
        $report->status = $request->status;
        $report->reviewer_note = $request->reviewer_note;
        $report->reviewer_id = Auth::id();
        $report->save();


        if ($request->status == 'resolved') {
            //close book
            Book::where('id', $report->book->id)->update([
                'is_active' => 0
            ]);
            //update book post
            $bookPost = Post::where('book_id', $report->book->id)->first();
            if ($bookPost) {
                $bookPost->allow_comments = 0;
                $bookPost->save();
            }
        }

        return $this->jsonResponseWithoutMessage('تم الاعتماد', 'data', 200);
    }
    public function showReport($id)
    {
        $report = ViolatedBook::with(['book', 'reviewer', 'reporter', 'media'])->findOrFail($id);
        $report->violated_pages = unserialize($report->violated_pages);

        return $this->jsonResponseWithoutMessage($report, 'data', 200);
    }
    public function listReportsForBook($book_id)
    {
        $reports = ViolatedBook::where('book_id', $book_id)->get();
        $reports->each(function ($report) {
            $report->pages = unserialize($report->pages);
        });

        return $this->jsonResponseWithoutMessage($reports, 'data', 200);
    }

    public function removeBookFromOsboha($book_id)
    {
        // Ensure the authenticated user is a admin|book_quality_team_coordinator
        if (!Auth::user()->hasanyrole('admin|book_quality_team_coordinator')) {
            throw new NotAuthorized;
        }
        Book::where('id', $book_id)->update([
            'is_active' => 0
        ]);
        //update book post
        $bookPost = Post::where('book_id', $book_id)->first();
        if ($bookPost) {
            $bookPost->allow_comments = 0;
            $bookPost->save();
        }

        return $this->jsonResponseWithoutMessage('تم سحب الكتاب من المنهج', 'data', 200);
    }
}
