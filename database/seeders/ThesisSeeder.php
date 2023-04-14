<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Mark;
use App\Models\Post;
use App\Models\Thesis;
use App\Models\ThesisType;
use App\Models\User;
use App\Models\Week;
use App\Traits\ThesisTraits;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

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
        $number = 10;
        $posts = Post::where('type_id', 2)->get();
        $datetime = Carbon::now()->startOfMonth()->subMonth(2);
        // $date_range = Carbon::createFromTimestamp($this->faker->dateTimeBetween($startDate = '-2 months', $endDate = 'now')->getTimeStamp());
        // $datetime = Carbon::createFromFormat('Y-m-d H:i:s', '2022-01-23 11:53:20');        
        for ($j = 0; $j < $number; $j++) {
            $date = $datetime->startOfWeek(Carbon::SUNDAY)->addWeeks();
            //if $date = '2023-03-26 00:00:00' then $date = '2023-03-26 01:00:00'
            if ($date == '2023-03-26 00:00:00') {
                $date = '2023-03-26 01:00:00';
            }

            Week::factory()->create([
                'created_at' => $date,
                'updated_at' => $date,
            ])->each(function ($week) use ($posts) {
                $users = User::where('is_excluded', 0)->where('is_hold', 0)->get();
                for ($i = 0; $i < count($users); $i++) {
                    Mark::factory(1)->create([
                        'user_id' => $users[$i]->id,
                        'week_id' => $week->id
                    ])->each(function ($mark) use ($users, $i, $posts) {
                        $post = $posts[rand(0, count($posts) - 1)];
                        Comment::factory(rand(1, 5))->create([
                            'type' => 'thesis',
                            'user_id' => $users[$i],
                            'post_id' => $post->id,
                        ])->each(function ($comment) use ($users, $i, $mark, $post) {


                            Thesis::factory(1)->create([
                                'comment_id' => $comment->id,
                                'book_id' => $post->book_id,
                                'mark_id' => $mark->id,
                                'user_id' => $users[$i]
                            ])->each(function ($thesis) use ($mark) {
                                $thesis_type = ThesisType::find($thesis->type_id)->first()->type;
                                $total_pages = $thesis->end_page - $thesis->start_page + 1;
                                $max_length = $thesis->max_length;
                                $total_screenshots = $thesis->total_screenshots;

                                $new_mark = [];
                                if ($thesis_type == 'normal') {
                                    $new_mark = $this->calculate_mark_for_normal_thesis($total_pages, $max_length, $total_screenshots);
                                } else {
                                    $new_mark = $this->calculate_mark_for_ramadan_thesis($total_pages, $max_length, $total_screenshots, $thesis_type);
                                }

                                $mark->total_pages += $total_pages;
                                $mark->total_screenshot += $total_screenshots;
                                $mark->total_thesis += ($max_length > 0 ? 1 : 0);
                                $mark->reading_mark = $new_mark['reading_mark'];
                                $mark->writing_mark = $new_mark['writing_mark'];


                                if ($mark->reading_mark > 50) {
                                    $mark->reading_mark = 50;
                                }

                                if ($mark->writing_mark > 40) {
                                    $mark->writing_mark = 40;
                                }

                                $mark->save();
                            });
                        });
                    });
                }
            });
        }


        // Thesis::factory(10)->create();
    }
}