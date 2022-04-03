<?php

namespace Database\Seeders;
namespace Carbon;

use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProfileSeeder extends Seeder
{
    //I moved this function to UserSeeder
   /* public function run()
    {
        $i=0;
        while ($i<=200){

            DB::table('user_profiles')->insert([
                'user_id' => rand(1,30),
                'timeline_id' => rand(1,30),
                'first_name_ar' => Str::random(10),
                'middle_name_ar' => Str::random(10),
                'last_name_ar' => Str::random(10),
                'country' => Str::random(10),
                'resident' => Str::random(10),
                'phone' => '+' . Str::random(12),
                'occupation' => Str::random(10),
                'birthdate' => Carbon::today()->subDays(rand(0, 365)),
                'bio' => Str::random(20),
                'cover_picture' => Str::random(10),
                'fav_writer' => Str::random(10),
                'fav_book' => Str::random(10),
                'fav_section' => Str::random(10),
                'fav_quote' => Str::random(30),
                'extraspace'=> 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.',
            ]);
            $i++;
        }
    }*/
}
