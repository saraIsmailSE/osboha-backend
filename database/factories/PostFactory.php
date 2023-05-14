<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $datetime = $this->faker->dateTimeBetween('-1 month', 'now');
        //add hour to datetime
        $datetime->setTime($datetime->format('H') + 1, $datetime->format('i'), $datetime->format('s'));


        return [
            'body' =>  $this->faker->paragraph,
            'timeline_id' => random_int(3, 181),
            'created_at' => $datetime,
            'updated_at' => $datetime
        ];
    }
}