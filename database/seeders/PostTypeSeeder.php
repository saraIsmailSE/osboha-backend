<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $post_type = ['noraml', 'book', 'article', 'infographic', 'support', 'discussion', 'announcement'];
        //POST TYPE
        foreach ($post_type as $type) {
            DB::table('post_types')->insert([
                'type' => $type,
            ]);
        }
    }
}