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
        $out_of_90 = random_int(0, 90);
        $support = $this->faker->randomElement([0, 10]);
        $out_of_100 = $out_of_90 + $support;
        $total_pages = random_int(0, 100);
        $total_thesis = random_int(0, 20);
        $total_screenshot = random_int(0, 20);
        return [
            'out_of_90' => 0,
            'out_of_100' => 0,
            'total_pages' => 0,
            'support' => $support,
            'total_thesis' => 0,
            'total_screenshot' => 0,
        ];
    }
}