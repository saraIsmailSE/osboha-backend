<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name,
                'description' => $this->faker->sentence(rand(2,3)),
                'cover_picture' => $this->faker->image(public_path('assets/images'),400,300, null, false),
                'creator_id' => rand(1,2),
        ];
    }
}
