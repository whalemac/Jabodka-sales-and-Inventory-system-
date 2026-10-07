<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL', null]),
            'version' => fake()->randomElement(['V1', 'V2', null]),
            'stock_count' => fake()->numberBetween(5, 40),
            'reorder_level' => 5,
            'is_active' => true,
        ];
    }

    public function withStock(int $quantity = 10): static
    {
        return $this->state(fn () => ['stock_count' => $quantity]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
