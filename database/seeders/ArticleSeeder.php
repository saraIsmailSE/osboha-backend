<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $section= ['علمي', 'تاريخي', 'ديني', 'سياسي' , 'انجليزي' , 'ثقافي' ,'تربوي' ,'تنمية'];
        for($i=0; $i<200; $i++){
            Article::create([
                'title' => Str::random(15),
                'post_id' => rand(1, 200),
                'user_id' => rand(1, 200),
                'section' => $section[rand(0,7)],
            ]);
        }
    }
}
