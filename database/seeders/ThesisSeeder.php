<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Comment;
use App\Models\Mark;
use App\Models\Post;
use App\Models\Thesis;
use App\Models\ThesisType;
use App\Models\User;
use App\Models\Week;
use App\Traits\ThesisTraits;
use Carbon\Factory;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Testing\WithFaker;

class ThesisSeeder extends Seeder
{
    use ThesisTraits;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // for ($i = 0; $i < 200; $i++) {
        //     $timestamp = mt_rand(1, time());
        //     $arr = [date("Y-m-d H:i:s", $timestamp), 0];
        //     Thesis::create([
        //         'comment_id' => rand(1, 200),
        //         'user_id' => rand(1, 200),
        //         'max_length' => rand(0, 1000),
        //         'book_id' => rand(1, 200),
        //         'type_id' => rand(1, 4),
        //         'mark_id' => rand(1, 2000),
        //         'total_pages' => rand(0, 100),
        //         'total_screenshots' => rand(0, 5),
        //         'is_acceptable' => array_rand($arr) ? date("Y-m-d H:i:s", $timestamp) : null,
        //     ]);
        // }
        $posts = Post::where('type_id', 2)->get();

        Week::factory(10)->create()->each(function ($week) use ($posts) {
            $users = User::where('is_excluded', 0)->where('is_hold', 0)->get();
            for ($i = 0; $i < count($users); $i++) {
                Mark::factory(1)->create([
                    'user_id' => $users[$i]->id,
                    'week_id' => $week->id
                ])->each(function ($mark) use ($users, $i, $posts) {
                    $post_id = $posts[rand(0, count($posts) - 1)]->id;
                    Comment::factory(rand(1, 5))->create([
                        'type' => 'thesis',
                        'user_id' => $users[$i],
                        'post_id' => $post_id,
                    ])->each(function ($comment) use ($users, $i, $mark, $post_id) {
                        $book = Book::where('post_id', $post_id)->first();

                        Thesis::factory(1)->create([
                            'comment_id' => $comment->id,
                            'book_id' => $book->id,
                            'mark_id' => $mark->id,
                            'user_id' => $users[$i]
                        ])->each(function ($thesis) use ($mark) {
                            $thesis_type = ThesisType::find($thesis->type_id)->first()->type;
                            $total_pages = $thesis->total_pages;
                            $max_length = $thesis->max_length;
                            $total_screenshots = $thesis->total_screenshots;

                            $new_mark = 0;
                            if ($thesis_type == 'normal') {
                                $new_mark = $this->calculate_mark_for_normal_thesis($total_pages, $max_length, $total_screenshots);
                            } else {
                                $new_mark = $this->calculate_mark_for_ramadan_thesis($total_pages, $max_length, $total_screenshots, $thesis_type);
                            }

                            $mark->total_pages += $total_pages;
                            $mark->total_screenshot += $total_screenshots;
                            $mark->total_thesis += ($max_length > 0 ? 1 : 0);
                            $mark->out_of_90 += $new_mark;

                            if ($mark->out_of_90 > 90) {
                                $mark->out_of_90 = 90;
                            }
                            $mark->out_of_100 = $mark->out_of_90;

                            if ($mark->support == SUPPORT_MARK && $mark->out_of_100 > 0) {
                                $mark->out_of_100 += SUPPORT_MARK;
                            }

                            $mark->save();
                        });
                    });
                });
            }
        });

        // Thesis::factory(10)->create();
    }
}