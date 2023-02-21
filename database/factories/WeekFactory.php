<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class WeekFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $title = $this->faker->title;
        $is_vacation = $this->faker->numberBetween(0, 1);
        return [
            'title' => rtrim($title, '.'),
            'is_vacation' => $is_vacation,
        ];
    }
}