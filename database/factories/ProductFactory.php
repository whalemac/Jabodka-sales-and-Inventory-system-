<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'supplier_id' => null,
            'name' => fake()->unique()->words(2, true),
            'category' => fake()->randomElement(['Apparel', 'Accessories', 'Home', 'Bags']),
            'source_type' => 'sourced',
            'base_price' => fake()->randomFloat(2, 100, 900),
            'is_active' => true,
        ];
    }

    public function handmade(): static
    {
        return $this->state(fn () => ['source_type' => 'handmade', 'supplier_id' => null]);
    }

    public function sourced(?Supplier $supplier = null): static
    {
        return $this->state(fn () => [
            'source_type' => 'sourced',
            'supplier_id' => $supplier?->id ?? Supplier::factory(),
        ]);
    }

    public function consignment(): static
    {
        return $this->state(fn () => ['source_type' => 'consignment', 'supplier_id' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
