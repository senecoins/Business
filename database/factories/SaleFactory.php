<?php

namespace Database\Factories;

use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::factory(),
            'total_amount' => $this->faker->randomFloat(2, 1, 1000),
            'payment_status' => $this->faker->randomElement(['pending', 'partial', 'paid']),
            'notes' => $this->faker->sentence,
        ];
    }
}
