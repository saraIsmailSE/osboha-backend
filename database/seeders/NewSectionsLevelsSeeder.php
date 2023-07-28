<?php

namespace Database\Seeders;

use App\Models\BookLevel;
use App\Models\BookType;
use App\Models\Section;
use Illuminate\Database\Seeder;

class NewSectionsLevelsSeeder extends Seeder
{
    public function run()
    {
        //SECTION - غير محدد
        $section = [
            ['section' => 'غير محدد', 'created_at' => now(), 'updated_at' => now()],
        ];
        Section::insert($section);

        //BOOK TYPES        
        $book_type = [
            ['type' => 'free', 'created_at' => now(), 'updated_at' => now()],
        ];
        BookType::insert($book_type);

        //BOOK LEVELS
        $book_level = [
            ['level'  => 'not_specified', 'arabic_level' => 'غير محدد', 'created_at' => now(), 'updated_at' => now()],
        ];
        BookLevel::insert($book_level);
    }
}
