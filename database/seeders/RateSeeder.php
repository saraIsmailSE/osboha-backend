<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Rate;
use App\Models\User;
use Illuminate\Database\Seeder;

class RateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //create unique rate for some posts by some users
       
        $posts = Post::inRandomOrder()->limit(10)->get();
        $users = User::inRandomOrder()->limit(40)->get();
        foreach ($posts as $post) {
            foreach ($users as $user) {
                $rate = new Rate();
                $rate->post_id = $post->id;
                $rate->user_id = $user->id;
                $rate->rate = rand(1, 5);
                $rate->save();
            }
        }
    }
}
