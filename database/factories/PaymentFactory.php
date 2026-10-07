<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\SalesTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'transaction_id' => SalesTransaction::factory()->online(),
            'user_id' => User::factory()->admin(),
            'payment_method' => 'gcash',
            'amount_paid' => 500,
            'payment_status' => 'pending',
            'payment_date' => null,
            'reference_number' => null,
        ];
    }

    public function confirmed(float $amount = 500): static
    {
        return $this->state(fn () => [
            'amount_paid' => $amount,
            'payment_status' => 'confirmed',
            'payment_date' => now(),
        ]);
    }
}
