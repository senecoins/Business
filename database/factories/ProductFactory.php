<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'barcode' => $this->faker->unique()->ean13,
            'purchase_price' => $this->faker->randomFloat(2, 1, 100),
            'selling_price' => $this->faker->randomFloat(2, 1, 100),
            'quantity' => $this->faker->numberBetween(0, 100),
            'security_stock' => 2,
            'active' => $this->faker->boolean,
            'image' => $this->faker->imageUrl(),
        ];
    }
}
