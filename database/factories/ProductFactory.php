<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Category;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $name = $this->faker->words(2, true),
            'description' => $this->faker->sentence(10),
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 9999),
            'category_id' => Category::factory(),
            'price_minor' => $this->faker->numberBetween(1000, 100000),
        ];
    }
}

