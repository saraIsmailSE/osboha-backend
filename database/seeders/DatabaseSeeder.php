<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(MarksSeeder::class);
        $this->call(RejectesThesesSeeder::class);
        $this->call(InfographicSeeder::class);
        $this->call(InfographicSeriesSeeder::class);
        $this->call(ArticleSeeder::class);
        $this->call(PermissionsSeeder::class);
        $this->call(CommentSeeder::class);
        $this->call(PostSeeder::class);
        $this->call(BookSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(FriendSeeder::class);
        $this->call(GroupSeeder::class);
        $this->call(UserGroupSeeder::class);
        $this->call(ThesisSeeder::class);
        $this->call(RateSeeder::class);
        $this->call(ReactionSeeder::class);
        $this->call(BookTypeSeeder::class);
        $this->call(ExceptionTypeSeeder::class);
        $this->call(GroupTypeSeeder::class);
        $this->call(PostTypeSeeder::class);
        $this->call(ThesisTypeSeeder::class);
        $this->call(TimelineTypeSeeder::class);
    }
}