<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->randomNumber(8),
            'product_uuid' => $this->faker->uuid(),
            'user_uuid' => $this->faker->uuid(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'canceled']),
            'total_price' => $this->faker->randomFloat(2, 1, 100),
            'quantity' => $this->faker->numberBetween(1, 10),
        ];
    }
}
