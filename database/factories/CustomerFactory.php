<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'buyer_type' => 'online',
            'contact_number' => fake()->numerify('09#########'),
            'shipping_address' => fake()->address(),
            'landmark' => fake()->randomElement([null, 'Near the plaza', 'Beside J&T']),
        ];
    }
}
