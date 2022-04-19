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
        $this->call(PermissionsSeeder::class);
        $this->call(CommentSeeder::class);
        $this->call(PostSeeder::class);
        $this->call(BookSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(FreindSeeder::class);
        $this->call(GroupSeeder::class);
        $this->call(ThesisSeeder::class);
        $this->call(RateSeeder::class);
        $this->call(ReactionSeeder::class);
    }
}