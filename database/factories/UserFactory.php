<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sso_id'        => $this->faker->unique()->numberBetween(100000, 999999999),
            'code'          => $this->faker->unique()->numerify('########'),
            'username'      => $this->faker->unique()->userName(),
            'type'          => 'TEACHER',
            'status'        => 'ACTIVE',
            'study_year'    => 0,
            'first_name_th' => $this->faker->firstName(),
            'last_name_th'  => $this->faker->lastName(),
            'first_name_en' => $this->faker->firstName(),
            'last_name_en'  => $this->faker->lastName(),
            'email'         => $this->faker->unique()->safeEmail(),
        ];
    }
}
