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
        $title = $this->faker->sentence(2);
        $is_vacation = $this->faker->numberBetween(0, 1);
        $date_range = Carbon::createFromTimestamp($this->faker->dateTimeBetween($startDate = '-2 months', $endDate = 'now')->getTimeStamp())->startOfWeek(Carbon::SUNDAY);
        $datetime = Carbon::createFromFormat('Y-m-d H:i:s', $date_range)->addWeeks();
        return [
            'title' => rtrim($title, '.'),
            'is_vacation' => $is_vacation,
            'created_at' => $datetime,
            'updated_at' => $datetime
        ];
    }
}