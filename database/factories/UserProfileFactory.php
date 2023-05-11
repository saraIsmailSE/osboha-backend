<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        return [
            'first_name_ar' => $this->faker->name,
            'middle_name_ar' => $this->faker->name,
            'last_name_ar' => $this->faker->name,
            'country' => $this->faker->countryCode,
            'resident' => $this->faker->countryCode,
            'phone' => '+' . $this->faker->phoneNumber,
            'occupation' => $this->faker->word,
            'birthdate' =>  $this->faker->dateTimeBetween('-1 month', 'now'),
            'bio' =>  $this->faker->sentence(rand(2, 3)),
            // 'cover_picture' => $this->faker->image(public_path('assets/images'), 400, 300, null, false),
            // 'profile_picture' => $this->faker->image(public_path('assets/images'), 400, 300, null, false),
            'fav_writer' => $this->faker->name,
            'fav_book' => $this->faker->word,
            'fav_section' => $this->faker->word,
            'fav_quote' => $this->faker->sentence(rand(5, 7)),
            'extraspace' => $this->faker->sentence(rand(5, 7)),
        ];
    }
}