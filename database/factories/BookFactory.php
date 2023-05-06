<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => rtrim($this->faker->sentence(random_int(1, 5)), '.'),
            'writer' => rtrim($this->faker->sentence(random_int(1, 5)), '.'),
            'publisher' => rtrim($this->faker->sentence(random_int(1, 5)), '.'),
            'brief' => $this->faker->paragraph(random_int(2, 5)),
            'start_page' => $this->faker->numberBetween(1, 30),
            'end_page' => $this->faker->numberBetween(100, 400),
            'link' => $this->faker->url(),
            'section_id' => $this->faker->numberBetween(1, 10),
            'type_id' => $this->faker->numberBetween(1, 2),
            'level_id' => rand(1, 3),
            'language_id' => rand(1, 2),
        ];
    }
}