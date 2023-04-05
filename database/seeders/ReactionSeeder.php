<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Reaction;
use App\Models\ReactionType;
use Illuminate\Database\Seeder;

class ReactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $reaction_types = [
            [
                'type' => 'like',
                'title' => 'إعجاب',
                'text_color' => '#278036',
            ],
            [
                'type' => 'love',
                'title' => 'أحببته',
                'text_color' => '#e91e63',
            ],
            [
                'type' => 'haha',
                'title' => 'أضحكني',
                'text_color' => '#fbc02d',

            ],
            [
                'type' => 'wow',
                'title' => 'أدهشني',
                'text_color' => '#ff9800',
            ],
            [
                'type' => 'sad',
                'title' => 'أحزنني',
                'text_color' => '#2196f3',
            ],
            [
                'type' => 'angry',
                'title' => 'أغضبني',
                'text_color' => '#f44336',
            ],
            [
                'type' => 'care',
                'title' => 'أهتم به',
                'text_color' => '#9c27b0',
            ]
        ];
        foreach ($reaction_types as $reaction_type) {
            $reaction_type = ReactionType::create($reaction_type);
            Media::create([
                'reaction_type_id' => $reaction_type->id,
                'type' => 'image',
                'media' => 'reactions/' . $reaction_type->type . '.svg',
                'user_id' => 1,
            ]);
        }

        //generate 100 reactions on different posts 
        for ($i = 0; $i < 100; $i++) {
            $reaction = Reaction::create([
                'user_id' => rand(1, 10),
                'type_id' => rand(1, count($reaction_types)),
                'post_id' => rand(20, 32),
            ]);
        }

        //generate 100 reactions on different comments
        for ($i = 0; $i < 100; $i++) {
            $reaction = Reaction::create([
                'user_id' => rand(1, 10),
                'type_id' => rand(1, count($reaction_types)),
                'comment_id' => rand(1, 10),
            ]);
        }
    }
}