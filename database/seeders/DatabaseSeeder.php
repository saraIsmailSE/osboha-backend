<?php

namespace Database\Seeders;

use App\Models\BookStatistics;
use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
       
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {

        // User::factory(20)->create()->each(function($user) {
        //     Post::factory(rand(1, 4))->create([
        //         'user_id' => $user->id
        //     ]);
        // });


        // $this->call(MarksSeeder::class);
        // $this->call(RejectesThesesSeeder::class);
        // $this->call(InfographicSeeder::class);
        // $this->call(InfographicSeriesSeeder::class);
        // $this->call(ArticleSeeder::class);
         $this->call(PermissionsSeeder::class);
        // $this->call(CommentSeeder::class);
        
        // $this->call(BookSeeder::class);
        $this->call(GroupSeeder::class);
<<<<<<< HEAD
        $this->call(UserGroupSeeder::class);
        $this->call(ThesisSeeder::class);
        $this->call(RateSeeder::class);
        $this->call(ReactionSeeder::class);
        $this->call(GroupTypeSeeder::class);
=======
        $this->call(UserSeeder::class);
        $this->call(PostSeeder::class);
        
        // $this->call(FriendSeeder::class);
        
        // $this->call(UserGroupSeeder::class);
        // $this->call(ThesisSeeder::class);
        // $this->call(RateSeeder::class);
        // $this->call(ReactionSeeder::class);
        // $this->call(BookTypeSeeder::class);
        // $this->call(ExceptionTypeSeeder::class);
        // $this->call(GroupTypeSeeder::class);
        // $this->call(PostTypeSeeder::class);
        // $this->call(ThesisTypeSeeder::class);
        // $this->call(TimelineTypeSeeder::class);
        // $this->call(BookStatisticsSeeder::class);
        // $this->call(WeekSeeder::class);

>>>>>>> f8263cc8d84c69b7cd7445f682b3fe4492efe3ed
    }

}