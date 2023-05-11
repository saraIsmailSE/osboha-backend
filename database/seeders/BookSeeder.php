<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Post;
use App\Models\Timeline;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $timeline_id = Timeline::where('type_id', 3)->first()->id;

        try {
            DB::beginTransaction();
            $csv = fopen(base_path('database/data/books.csv'), 'r');
            $books = [];

            while (($row = fgetcsv($csv)) !== false) {

                $bookData = [
                    'name' => $row[1],
                    'end_page' => $row[2],
                    'section_id' => $row[3],
                    'level_id' => $row[4],
                    'created_at' => date('Y-m-d H:i:s', strtotime($row[5])),
                    'updated_at' => date('Y-m-d H:i:s', strtotime($row[6])),
                    'writer' => $row[7],
                    'publisher' => $row[8],
                    'start_page' => $row[9],
                    'link' => $row[10],
                    'brief' => $row[11],
                    'language_id' => $row[12],
                    'type_id' => $row[13]
                ];

                // Validate input data
                $validator = Validator::make($bookData, [
                    'name' => 'required|string',
                    'end_page' => 'required|integer',
                    'section_id' => 'required|integer',
                    'level_id' => 'required|integer',
                    'created_at' => 'required|date',
                    'updated_at' => 'required|date',
                    'writer' => 'required|string',
                    'publisher' => 'required|string',
                    'start_page' => 'required|integer',
                    'link' => 'nullable|url',
                    'brief' => 'nullable|string',
                    'language_id' => 'required|integer',
                    'type_id' => 'required|integer',
                ]);

                if ($validator->fails()) {
                    // Handle validation errors
                    continue;
                }

                $books[] = $bookData;
            }

            fclose($csv);

            Book::insert($books);

            // Create a Post record for each book
            $posts = [];
            $books = Book::all();
            foreach ($books as $book) {
                $posts[] = [
                    'user_id' => 1,
                    'type_id' => 2,
                    'timeline_id' => $timeline_id,
                    'book_id' => $book->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            Post::insert($posts);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}
