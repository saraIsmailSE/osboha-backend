<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MarkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $support = $this->faker->randomElement([0, 10]);
        return [
            'reading_mark' => 0,
            'writing_mark' => 0,
            'total_pages' => 0,
            'support' => $support,
            'total_thesis' => 0,
            'total_screenshot' => 0,
        ];
    }
}