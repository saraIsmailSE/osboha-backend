<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProfileSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'posts' => $this->faker->randomNumber(1, 3),
            'media' => $this->faker->randomNumber(1, 3),
            'certificates' => $this->faker->randomNumber(1, 3),
            'infographics' => $this->faker->randomNumber(1, 3),
            'articles' => $this->faker->randomNumber(1, 3),
            'thesis' => $this->faker->randomNumber(1, 3),
            'books' => $this->faker->randomNumber(1, 3),
            'marks' => $this->faker->randomNumber(1, 3),
        ];
    }
}
