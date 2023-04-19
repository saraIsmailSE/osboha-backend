<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Mark;
use App\Models\Media;
use App\Models\Post;
use App\Models\ThesisType;
use App\Models\User;
use App\Models\Week;
use App\Traits\ThesisTraits;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ThesisSeeder extends Seeder
{
    use ThesisTraits;

    private function search_for_week_title($date, $year_weeks)
    {
        foreach ($year_weeks as $val) {
            if ($val['date'] === $date) {
                return $val['title'];
            }
        }
        return null;
    }

    private function weeks()
    {
        //get date of first day of first week of february 2023
        $date = Carbon::createFromDate(2023, 1, 29)->format('Y-m-d');
        return array(
            array('title' => 'الاول من فبراير', 'date' => $date),
            array('title' => 'الثاني من فبراير', 'date' => Carbon::parse($date)->addWeeks()->format('Y-m-d')),
            array('title' => 'الثالث من فبراير', 'date' => Carbon::parse($date)->addWeeks(2)->format('Y-m-d')),
            array('title' => 'الرابع من فبراير', 'date' => Carbon::parse($date)->addWeeks(3)->format('Y-m-d')),
            array('title' => 'الاول من مارس', 'date' => Carbon::parse($date)->addWeeks(4)->format('Y-m-d')),
            array('title' => 'الثاني من مارس', 'date' => Carbon::parse($date)->addWeeks(5)->format('Y-m-d')),
            array('title' => 'الثالث من مارس', 'date' => Carbon::parse($date)->addWeeks(6)->format('Y-m-d')),
            array('title' => 'الرابع من مارس', 'date' => Carbon::parse($date)->addWeeks(7)->format('Y-m-d')),
            array('title' => 'الاول من ابريل', 'date' => Carbon::parse($date)->addWeeks(8)->format('Y-m-d')),
            array('title' => 'الثاني من ابريل', 'date' => Carbon::parse($date)->addWeeks(9)->format('Y-m-d')),
            array('title' => 'الثالث من ابريل', 'date' => Carbon::parse($date)->addWeeks(10)->format('Y-m-d')),
            array('title' => 'الرابع من ابريل', 'date' => Carbon::parse($date)->addWeeks(11)->format('Y-m-d')),
            array('title' => 'الخامس من ابريل', 'date' => Carbon::parse($date)->addWeeks(12)->format('Y-m-d')),
            array('title' => 'الاول من مايو', 'date' => Carbon::parse($date)->addWeeks(13)->format('Y-m-d')),
            array('title' => 'الثاني من مايو', 'date' => Carbon::parse($date)->addWeeks(14)->format('Y-m-d')),
            array('title' => 'الثالث من مايو', 'date' => Carbon::parse($date)->addWeeks(15)->format('Y-m-d')),
            array('title' => 'الرابع من مايو', 'date' => Carbon::parse($date)->addWeeks(16)->format('Y-m-d')),
            array('title' => 'الاول من يونيو', 'date' => Carbon::parse($date)->addWeeks(17)->format('Y-m-d')),
            array('title' => 'الثاني من يونيو', 'date' => Carbon::parse($date)->addWeeks(18)->format('Y-m-d')),
            array('title' => 'الثالث من يونيو', 'date' => Carbon::parse($date)->addWeeks(19)->format('Y-m-d')),
            array('title' => 'الرابع من يونيو', 'date' => Carbon::parse($date)->addWeeks(20)->format('Y-m-d')),
            array('title' => 'الاول من يوليو', 'date' => Carbon::parse($date)->addWeeks(21)->format('Y-m-d')),
            array('title' => 'الثاني من يوليو', 'date' => Carbon::parse($date)->addWeeks(22)->format('Y-m-d')),
            array('title' => 'الثالث من يوليو', 'date' => Carbon::parse($date)->addWeeks(23)->format('Y-m-d')),
            array('title' => 'الرابع من يوليو', 'date' => Carbon::parse($date)->addWeeks(24)->format('Y-m-d')),
            array('title' => 'الخامس من يوليو', 'date' => Carbon::parse($date)->addWeeks(25)->format('Y-m-d')),
            array('title' => 'الاول من اغسطس', 'date' => Carbon::parse($date)->addWeeks(26)->format('Y-m-d')),
            array('title' => 'الثاني من اغسطس', 'date' => Carbon::parse($date)->addWeeks(27)->format('Y-m-d')),
            array('title' => 'الثالث من اغسطس', 'date' => Carbon::parse($date)->addWeeks(28)->format('Y-m-d')),
            array('title' => 'الرابع من اغسطس', 'date' => Carbon::parse($date)->addWeeks(29)->format('Y-m-d')),
            array('title' => 'الاول من سبتمبر', 'date' => Carbon::parse($date)->addWeeks(30)->format('Y-m-d')),
            array('title' => 'الثاني من سبتمبر', 'date' => Carbon::parse($date)->addWeeks(31)->format('Y-m-d')),
            array('title' => 'الثالث من سبتمبر', 'date' => Carbon::parse($date)->addWeeks(32)->format('Y-m-d')),
            array('title' => 'الرابع من سبتمبر', 'date' => Carbon::parse($date)->addWeeks(33)->format('Y-m-d')),
            array('title' => 'الخامس من سبتمبر', 'date' => Carbon::parse($date)->addWeeks(34)->format('Y-m-d')),
            array('title' => 'الاول من اكتوبر', 'date' => Carbon::parse($date)->addWeeks(35)->format('Y-m-d')),
            array('title' => 'الثاني من اكتوبر', 'date' => Carbon::parse($date)->addWeeks(36)->format('Y-m-d')),
            array('title' => 'الثالث من اكتوبر', 'date' => Carbon::parse($date)->addWeeks(37)->format('Y-m-d')),
            array('title' => 'الرابع من اكتوبر', 'date' => Carbon::parse($date)->addWeeks(38)->format('Y-m-d')),
            array('title' => 'الخامس من اكتوبر', 'date' => Carbon::parse($date)->addWeeks(39)->format('Y-m-d')),
            array('title' => 'الاول من نوفمبر', 'date' => Carbon::parse($date)->addWeeks(40)->format('Y-m-d')),
            array('title' => 'الثاني من نوفمبر', 'date' => Carbon::parse($date)->addWeeks(41)->format('Y-m-d')),
            array('title' => 'الثالث من نوفمبر', 'date' => Carbon::parse($date)->addWeeks(42)->format('Y-m-d')),
            array('title' => 'الرابع من نوفمبر', 'date' => Carbon::parse($date)->addWeeks(43)->format('Y-m-d')),
            array('title' => 'الاول من ديسمبر', 'date' => Carbon::parse($date)->addWeeks(44)->format('Y-m-d')),
            array('title' => 'الثاني من ديسمبر', 'date' => Carbon::parse($date)->addWeeks(45)->format('Y-m-d')),
            array('title' => 'الثالث من ديسمبر', 'date' => Carbon::parse($date)->addWeeks(46)->format('Y-m-d')),
            array('title' => 'الرابع من ديسمبر', 'date' => Carbon::parse($date)->addWeeks(47)->format('Y-m-d')),
            array('title' => 'الخامس من ديسمبر', 'date' => Carbon::parse($date)->addWeeks(48)->format('Y-m-d')),
        );
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $number = 3;
        $posts = Post::where('type_id', 2)->get();
        // $datetime = Carbon::now()->startOfMonth()->subMonth(2);
        $datetime = Carbon::now()->startOfMonth();
        for ($j = 0; $j < $number; $j++) {
            $date = $datetime->startOfWeek(Carbon::SUNDAY)->addWeek();

            $week_id =  Week::create([
                'title' => $this->search_for_week_title(Carbon::parse($date)->format('Y-m-d'), $this->weeks()),
                // 'is_vacation' => rand(0, 1),
                'is_vacation' => 0,
                'main_timer' =>  Carbon::parse($date)->addDays(6)->addHours(23)->addMinutes(59)->addSeconds(59),
                'created_at' => $date,
                'updated_at' => $date,
            ])->id;
            $users = User::where('is_excluded', 0)->where('is_hold', 0)->get();
            for ($i = 0; $i < count($users); $i++) {
                $mark_id =  Mark::factory()->create([
                    'user_id' => $users[$i]->id,
                    'week_id' => $week_id
                ])->id;
                $post = $posts[random_int(0, count($posts) - 1)];
                Comment::factory(random_int(1, 2))->create([
                    'type' => 'thesis',
                    'user_id' => $users[$i],
                    'post_id' => $post->id,
                ])->each(function ($comment) use ($post, $mark_id, $users, $i) {
                    $thesis['comment_id'] = $comment->id;
                    $thesis['book_id'] = $post->book_id;
                    $thesis['max_length'] = $comment->body ? Str::length(trim($comment->body)) : 0;
                    $thesis['total_screenshots'] = $thesis['max_length'] > 0 ? 0 : random_int(0, random_int(1, 5));
                    $thesis['start_page'] = random_int(0, random_int(6, 30));
                    $thesis['end_page'] =  $thesis['start_page'] > 0 ? random_int($thesis['start_page'] + 6, 50) : 0;
                    $thesis['type_id'] =  ThesisType::where('type', $post->book->type->type)->first()->id;
                    $thesis['user_id'] = $users[$i]->id;
                    $thesis['mark_id'] = $mark_id;

                    //to add media for thesis - uncomment this if you want to add media for thesis
                    // if ($thesis['total_screenshots'] > 0) {
                    //     //create media for first comment
                    //     $comment->type = 'screenshot';
                    //     $comment->save();

                    //     Media::factory()->create([
                    //         'user_id' => $users[$i]->id,
                    //         'comment_id' => $comment->id,
                    //     ]);

                    //     for ($k = 1; $k < $thesis['total_screenshots']; $k++) {
                    //         $reply =  Comment::create([
                    //             'type' => 'screenshot',
                    //             'user_id' => $users[$i]->id,
                    //             'post_id' => $post->id,
                    //             'comment_id' => $comment->id,
                    //         ]);

                    //         Media::factory()->create([
                    //             'user_id' => $users[$i]->id,
                    //             'comment_id' => $reply->id,
                    //         ]);
                    //     }
                    // }

                    return $this->createThesis($thesis, true);
                });
            }
        }


        // Thesis::factory(10)->create();
    }
}