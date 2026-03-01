<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HeaderLogoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'image' => 'uploads/header-logos/' . $this->faker->uuid . '.png',
        ];
    }
}
