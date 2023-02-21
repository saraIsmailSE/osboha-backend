<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Media;
use App\Models\Post;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        //     $level= ['بسيط', 'متوسط', 'عميق'];
        //    $i=0;
        //     while ($i<=200){

        //         DB::table('books')->insert([
        //             'post_id' => rand(0,15),
        //             'name' => Str::random(10),
        //             'writer' => Str::random(10),
        //             'publisher' => Str::random(10),
        //             'brief' => Str::random(150),
        //             'start_page' => 1,
        //             'end_page' => rand(150,600),
        //             'link' => 'https://www.google.com/',
        //             'section_id' =>rand(1,7),
        //             'type_id' => rand(1,3),
        //             'level' => $level[rand(0,2)],
        //         ]);
        //         $i++;    
        //     }

        // Post::factory(100)->create([
        //     'user_id' => rand(1, 3),
        //     'type_id' => 2,
        //     'timeline_id' => 1,
          
        // ])->each(function ($post) {
        //     Book::factory()->create([
        //         'post_id' => $post->id,
        //     ]);
        // });

        Book::factory(100)->create()->each(function ($book) {
            $user_id = rand(1,3);
            $book->posts()->save(Post::factory()->create([
                'user_id' => $user_id,
                'type_id' => 2,
                'timeline_id' => 3,
            ]));
            $book->media()->save(Media::factory()->create([
                'user_id' => $user_id
            ]));
        });
    }
}