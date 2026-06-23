<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sso_id'        => fake()->unique()->numberBetween(100000, 999999999),
            'code'          => fake()->unique()->numerify('########'),
            'username'      => fake()->unique()->userName(),
            'type'          => 'TEACHER',
            'status'        => 'ACTIVE',
            'study_year'    => 0,
            'first_name_th' => fake()->firstName(),
            'last_name_th'  => fake()->lastName(),
            'first_name_en' => fake()->firstName(),
            'last_name_en'  => fake()->lastName(),
            'email'         => fake()->unique()->safeEmail(),
        ];
    }
}
