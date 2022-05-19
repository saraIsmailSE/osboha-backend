<?php
namespace Database\Seeders;

use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $i=0;
        $gender= ['male', 'female'];
        while ($i<=200){
            $user = \App\Models\User::factory()->create([
                'name' => Str::random(10),
                'email' => Str::random(10).'@gmail.com',
                'password' => Hash::make('password'),
                //'email_verified_at' => Carbon::today()->subDays(rand(0, 365)),
                'is_blocked' => rand(0,1),
                'gender' => $gender[rand(0,1)],
                'is_hold' =>  rand(0,1),
                'is_excluded' => rand(0,1),
            ]);

            $user->assignRole(rand(0,3));            

            DB::table('user_profiles')->insert([
                'user_id' => $user->id,
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


            DB::table('profile_settings')->insert([
                'user_id' => $user->id,
                'posts' => rand(1,3),
                'media' => rand(1,3),
                'certificates' => rand(1,3),
                'infographics' => rand(1,3),
                'articles' => rand(1,3),
                'thesis' => rand(1,3),
                'books' => rand(1,3),
                'marks' => rand(1,3),
            ]);
            $i++;    
         }
    }
} 