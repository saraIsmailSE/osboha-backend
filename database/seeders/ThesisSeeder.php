<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Mark;
use App\Models\Media;
use App\Models\Post;
use App\Models\ThesisType;
use App\Models\User;
use App\Models\Week;
use App\Traits\MediaTraits;
use App\Traits\ThesisTraits;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ThesisSeeder extends Seeder
{
    use ThesisTraits, MediaTraits;

    private function search_for_week_title($date, $year_weeks)
    {
        foreach ($year_weeks as $val) {
            if ($val['date'] === $date) {
                return $val['title'];
            }
        }
        return null;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $number = 4;
        $posts = Post::where('type_id', 2)->get();
        // $datetime = Carbon::now()->startOfMonth()->startOfWeek(Carbon::SATURDAY);
        // //go back 3 weeks
        // $datetime = Carbon::parse($datetime)->subWeeks($number);
        for ($j = $number; $j >= 0; $j--) {
            $date = Carbon::now()->startOfMonth()->startOfWeek(Carbon::SATURDAY)->subWeeks($j);
            $dateToSearch = $date->addDay();
            $title = $this->search_for_week_title(Carbon::parse($dateToSearch)->format('Y-m-d'), config('constants.YEAR_WEEKS'));
            $dateToAdd = $date->subDay()->addHours(23)->addMinutes(59)->addSeconds(59);
            $week_id =  Week::create([
                'title' => $title,
                'is_vacation' => 0,
                'main_timer' =>  Carbon::parse($dateToAdd)->addDays(7),
                'created_at' => $dateToAdd,
                'updated_at' => $dateToAdd,
            ])->id;
            $users = User::where('is_excluded', 0)->where('is_hold', 0)->pluck('id')->toArray();
            foreach ($users as $i) {
                $mark_id =  Mark::factory()->create([
                    'user_id' => $i,
                    'week_id' => $week_id
                ])->id;
                $post = $posts->random();
                Comment::factory(random_int(1, 2))->create([
                    'type' => 'thesis',
                    'user_id' => $i,
                    'post_id' => $post->id,
                ])->each(function ($comment) use ($post, $mark_id, $i) {
                    $thesis['comment_id'] = $comment->id;
                    $thesis['book_id'] = $post->book_id;
                    $thesis['max_length'] = $comment->body ? Str::length(trim($comment->body)) : 0;
                    $thesis['total_screenshots'] = $thesis['max_length'] > 0 ? 0 : random_int(0, random_int(1, 5));
                    $thesis['start_page'] = random_int(0, random_int(6, 30));
                    $thesis['end_page'] =  $thesis['start_page'] > 0 ? random_int($thesis['start_page'] + 6, 50) : 0;
                    $thesis['type_id'] =  ThesisType::where('type', $post->book->type->type)->first()->id;
                    $thesis['user_id'] = $i;
                    $thesis['mark_id'] = $mark_id;

                    //to add media for thesis - uncomment this if you want to add media for thesis
                    // if ($thesis['total_screenshots'] > 0) {
                    //     //create media for first comment
                    //     $comment->type = 'screenshot';
                    //     $comment->save();

                    //     $media = [];

                    //     $media[] = [
                    //         'user_id' => $i,
                    //         'comment_id' => $comment->id,
                    //         'media' => $this->getRandomMediaFileName(),
                    //         'type' => 'image',
                    //         'created_at' => Carbon::now(),
                    //         'updated_at' => Carbon::now(),
                    //     ];

                    //     for ($k = 1; $k < $thesis['total_screenshots']; $k++) {
                    //         Comment::create([
                    //             'type' => 'screenshot',
                    //             'user_id' => $i,
                    //             'post_id' => $post->id,
                    //             'comment_id' => $comment->id,
                    //         ])->each(function ($reply) {
                    //             $media[] = [
                    //                 'user_id' => $reply->user_id,
                    //                 'comment_id' => $reply->id,
                    //                 'media' => $this->getRandomMediaFileName(),
                    //                 'type' => 'image',
                    //                 'created_at' => Carbon::now(),
                    //                 'updated_at' => Carbon::now(),
                    //             ];
                    //         });
                    //     }
                    //     Media::insert($media);
                    // }

                    return $this->createThesis($thesis, true);
                });
            }
        }
    }
}