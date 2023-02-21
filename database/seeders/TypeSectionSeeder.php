<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $section = ['علمي', 'تاريخي', 'ديني', 'سياسي', 'انجليزي', 'ثقافي', 'تربوي', 'تنمية', 'سيرة', 'اجتماعي '];
        $i = 0;
        //SECTIONS
        while ($i <= 7) {
            DB::table('sections')->insert([

                'section' => $section[$i],

            ]);
            $i++;
        }

        $book_type = ['normal', 'ramadan', 'young', 'kids', 'tafseer'];
        //BOOK TYPE
        $i = 0;
        while ($i <= 3) {
            DB::table('book_types')->insert([

                'type' => $book_type[$i],

            ]);
            $i++;
        }

        $thesis_type = ['normal', 'ramadan', 'young', 'kids'];
        //THESIS TYPE
        $i = 0;
        while ($i <= 3) {
            DB::table('thesis_types')->insert([

                'type' => $thesis_type[$i],

            ]);
            $i++;
        }


        $post_type = ['normal', 'book', 'article', 'infographic', 'support', 'discussion'];
        //POST TYPE
        $i = 0;
        while ($i <= 5) {
            DB::table('post_types')->insert([

                'type' => $post_type[$i],

            ]);
            $i++;
        }
        $group_type = ['reading', 'working', 'supervising'];
        //GROUP TYPE
        $i = 0;
        while ($i <= 2) {
            DB::table('group_types')->insert([

                'type' => $group_type[$i],

            ]);
            $i++;
        }

        $languages = ['arabic', 'english'];
        for($i=0; $i<count($languages); $i++){
            DB::table('languages')->insert([
                'language' => $languages[$i],
            ]);
        }
    }
}
