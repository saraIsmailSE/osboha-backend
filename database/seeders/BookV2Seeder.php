<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Post;
use App\Models\Timeline;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookV2Seeder extends Seeder
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
            // Create a Post record for each book
            $posts = [];
            $books = Book::whereIn('id', [
                14,
                26,
                28,
                29,
                30,
                37,
                40,
                41,
                45,
                46,
                48,
                56,
                60,
                76,
                77,
                79,
                85,
                90,
                127,
                128,
                130,
                153,
                170,
                179,
                186,
                187,
                188,
                192,
                203,
                213,
                214,
                218,
                227,
                237,
                244,
                248,
                261,
                293,
                340,
                359,
                361,
                372,
                395,
                397,
                409,
                425,
                427,
                434,
                439,
                440,
                445,
                446,
                447,
                448,
                456,
                457,
                474,
                483,
                485,
                487,
                489,
                493,
                494,
                496,
                499,
                500,
                511,
                517,
                523,
                551,
                559,
                608,
                634,
                642,
                643,
                674,
                675,
                676,
                677,
                678,
                679,
                680,
                681,
                682,
                683,
                684,
                685,
                686,
                687,
                688,
                689,
                690,
                691,
                692,
                693,
                694,
                695,
                696,
                697,
                698,
                699,
                700,
                701,
                702,
                703,
                704,
                705,
                706,
                707,
                708,
                709,
                710,
                711,
                712,
                713,
                714,
                715,
                716,
                717,
                718,
                719
            ])->get();
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
