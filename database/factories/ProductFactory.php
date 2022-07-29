<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'owner_id' => User::first()->id,
            'name' => $this->faker->words(rand(1,3),true),
            'price'=> rand(10,80),
            'image_path'=> "products/shared/".rand(40,55).".jpg"
        ];
    }
}
