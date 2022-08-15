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

        return [
            'body'=>  $this->faker->paragraph,
            'type_id' => (rand(1, 4)), 
            'timeline_id' =>(rand(1, 5)),
            'created_at' => $datetime,
            'updated_at' => $datetime
        ];
    }
}
