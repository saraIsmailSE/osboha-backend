<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'receiver_id' => 0,
            //'status' => rand(0,1),
            'body'=>  $this->faker->paragraph,
        ];
    }
}
