<?php

namespace Database\Seeders;

use App\Models\BookType;
use App\Models\ExceptionType;
use App\Models\GroupType;
use App\Models\Language;
use App\Models\PostType;
use App\Models\Section;
use App\Models\ThesisType;
use App\Models\TimelineType;
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
        //SECTIONS
        $sections = ['علمي', 'تاريخي', 'ديني', 'سياسي', 'انجليزي', 'ثقافي', 'تربوي', 'تنمية', 'سيرة', 'اجتماعي '];
        foreach ($sections as $section) {
            Section::create([
                'section' => $section,
            ]);
        }

        //BOOK TYPES
        $book_types = ['normal', 'ramadan', 'young', 'kids', 'tafseer'];
        foreach ($book_types as $type) {
            BookType::create(['type' => $type]);
        }

        //THESIS TYPE
        $thesis_types = ['normal', 'ramadan', 'young', 'kids', 'tafseer'];
        foreach ($thesis_types as $type) {
            ThesisType::create(['type' => $type]);
        }


        //POST TYPES
        $post_type = ['normal', 'book', 'article', 'infographic', 'support', 'discussion', 'announcement'];
        foreach ($post_type as $type) {
            PostType::create(['type' => $type]);
        }

        //GROUP TYPES
        
        $group_type = ['followup','supervising','advising','consultation','Administration'];
        foreach ($group_type as $type) {
            GroupType::create(['type' => $type]);
        }

        //LANGUAGES
        $languages = ['arabic', 'english'];
        foreach ($languages as $language) {
            Language::create(['language' => $language]);
        }

        //TIMELINE TYPES
        $timeline_types = ['main', 'profile', 'book', 'group'];
        foreach ($timeline_types as $type) {
            TimelineType::create([
                'type' => $type,
                'description' => 'simple desc',

            ]);
        }

        //EXCEPTION TYPES
        $exception_types = ['تجميد الأسبوع الحالي', 'تجميد الأسبوع القادم', 'نظام امتحانات - شهري', 'نظام امتحانات - فصلي', 'تجميد استثنائي'];
        foreach ($exception_types as $type) {
            ExceptionType::create([
                'type' => $type,
            ]);
        }
    }
}