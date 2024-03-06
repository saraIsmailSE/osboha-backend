<?php

namespace Database\Seeders;

use App\Models\PostType;
use Illuminate\Database\Seeder;

class PostTypesFridayThesis extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        //POST TYPE
        $post_types = [
            ['type' => 'friday-thesis', 'created_at' => now(), 'updated_at' => now()],
        ];
        PostType::insert($post_types);
    }
}
