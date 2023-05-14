<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Comment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        // Post on Main Timeline
        //'user_id' => rand(1,3) [users have permition]

        // 'type_id' =>1 [Normal]
        // 'timeline_id' => [Main]

        Post::factory(50)->create([
            'user_id' => rand(1, 100),
            'type_id' => 1,
            // 'timeline_id' => 1
        ])->each(function ($post) {
            $post->taggedUsers()->create([
                'user_id' => rand(101, 150),
            ]);
            Comment::factory(rand(1, 10))->create([
                'type' => 'normal',
                'user_id' => rand(1, 150),
                'post_id' => $post->id,
            ])->each(function ($comment) use ($post) {
                // replies
                Comment::factory(rand(1, 5))->create([
                    'user_id' => rand(1, 150),
                    'type' => 'replay',
                    'post_id' => $post->id,
                    'comment_id' => $comment->id
                ]);
            });
        });
    }
}