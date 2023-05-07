<?php

namespace Database\Seeders;

use App\Models\BookLevel;
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
        $sections = [
            ['section' => 'تنمية', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'فكري', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'تربية', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'اجتماعي', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'تاريخي', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'أدبي', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'سياسي', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'علمي', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'ديني', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'إقتصادي', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'عسكري', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'سير الصحابة', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'انجليزي', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'خيال علمي / أطفال', 'created_at' => now(), 'updated_at' => now()],
            ['section' => 'انجليزي', 'created_at' => now(), 'updated_at' => now()],
        ];

        Section::insert($sections);

        //BOOK TYPES

        $book_types = [
            ['type' => 'normal', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'ramadan', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'young', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'kids', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'tafseer', 'created_at' => now(), 'updated_at' => now()],
        ];
        BookType::insert($book_types);

        //THESIS TYPE       
        $thesis_types = [
            ['type' => 'normal', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'ramadan', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'young', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'kids', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'tafseer', 'created_at' => now(), 'updated_at' => now()],
        ];
        ThesisType::insert($thesis_types);

        //POST TYPES
        $post_types = [
            ['type' => 'normal', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'book', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'article', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'infographic', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'support', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'discussion', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'announcement', 'created_at' => now(), 'updated_at' => now()],
        ];
        PostType::insert($post_types);

        //GROUP TYPES        
        $group_types = [
            ['type' => 'followup', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'supervising', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'advising', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'consultation', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'Administration', 'created_at' => now(), 'updated_at' => now()],
        ];
        GroupType::insert($group_types);

        //LANGUAGES        
        $languages = [
            ['language' => 'arabic', 'created_at' => now(), 'updated_at' => now()],
            ['language' => 'english', 'created_at' => now(), 'updated_at' => now()],
        ];
        Language::insert($languages);

        //TIMELINE TYPES
        $timeline_types = [
            ['type' => 'main', 'description' => 'simple desc', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'profile', 'description' => 'simple desc', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'book', 'description' => 'simple desc', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'group', 'description' => 'simple desc', 'created_at' => now(), 'updated_at' => now()],
        ];
        TimelineType::insert($timeline_types);

        //EXCEPTION TYPES        
        $exception_types = [
            ['type' => 'تجميد الأسبوع الحالي', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'تجميد الأسبوع القادم', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'نظام امتحانات - شهري', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'نظام امتحانات - فصلي', 'created_at' => now(), 'updated_at' => now()],
            ['type' => 'تجميد استثنائي', 'created_at' => now(), 'updated_at' => now()],
        ];
        ExceptionType::insert($exception_types);

        //BOOK LEVELS
        $book_levels = [
            ['level'  => 'simple', 'arabic_level' => 'بسيط', 'created_at' => now(), 'updated_at' => now()],
            ['level'  => 'intermediate', 'arabic_level' => 'متوسط', 'created_at' => now(), 'updated_at' => now()],
            ['level'  => 'advanced', 'arabic_level' => 'عميق', 'created_at' => now(), 'updated_at' => now()],
        ];
        BookLevel::insert($book_levels);
    }
}