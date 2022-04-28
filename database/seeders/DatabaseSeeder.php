<?php

namespace Database\Seeders;

use App\Models\RejectedMark;
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
        $this->call([
            MarksSeeder::class,
            RejectedMarksSeeder::class,
            InfographicSeeder::class,
            InfographicSeriesSeeder::class,
            ArticleSeeder::class
        ]);
        $this->call(PermissionsSeeder::class);
        $this->call(CommentSeeder::class);
        $this->call(PostSeeder::class);
        $this->call(BookSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(FreindSeeder::class);
        $this->call(GroupSeeder::class);
        $this->call(UserGroupSeeder::class);
    }
}