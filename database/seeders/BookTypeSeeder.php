<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $book_type = ['normal', 'ramadan', 'tafseer', 'young', 'kids'];
        //BOOK TYPE
        $i = 0;
        while ($i <= 4) {
            DB::table('book_types')->insert([

                'type' => $book_type[$i],

            ]);
            $i++;
        }
    }
}