<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Seeder;

class ArticlesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Article::create([
            'title' => 'New Book added',
            'post_id' => '1',
            'user_id' => '1',
            'section' => 'section 1', 
        ]);

        Article::create([
            'title' => 'twitter',
            'post_id' => '2',
            'user_id' => '3',
            'section' => 'compitations', 
        ]);

        Article::create([
            'title' => 'infographic course',
            'post_id' => '3',
            'user_id' => '2',
            'section' => 'courses', 
        ]);
    }
}
