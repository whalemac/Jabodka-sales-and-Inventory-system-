<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\SalesTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesTransaction>
 */
class SalesTransactionFactory extends Factory
{
    protected $model = SalesTransaction::class;

    public function definition(): array
    {
        return [
            'customer_id' => null,
            'user_id' => User::factory()->admin(),
            'channel' => 'online',
            'transaction_date' => now(),
            'shipping_fee' => 0,
            'courier' => 'J&T Express',
            'tracking_number' => null,
            'shipment_status' => 'pending',
        ];
    }

    public function online(): static
    {
        return $this->state(fn () => [
            'channel' => 'online',
            'customer_id' => Customer::factory(),
            'shipment_status' => 'pending',
        ]);
    }

    public function walkIn(): static
    {
        return $this->state(fn () => [
            'channel' => 'walk_in',
            'customer_id' => null,
            'shipment_status' => null,
        ]);
    }

    public function shipmentStatus(string $status): static
    {
        return $this->state(fn () => ['shipment_status' => $status]);
    }
}
