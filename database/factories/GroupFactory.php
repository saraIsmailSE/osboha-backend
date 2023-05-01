<?php

namespace Database\Factories;

use App\Models\Timeline;
use App\Models\TimelineType;
use App\Traits\MediaTraits;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    use MediaTraits;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $timeline_type = TimelineType::where('type', 'group')->first()->id;
        return [
            'name' => $this->faker->name,
            'description' => $this->faker->sentence(rand(2, 3)),
            // 'cover_picture' => $this->faker->image(public_path('assets/images'),400,300, null, false),
            'timeline_id' => Timeline::create(['type_id' => $timeline_type])->id,
            'cover_picture' => $this->getRandomMediaFileName(),
            'creator_id' => rand(1, 2),
        ];
    }
}