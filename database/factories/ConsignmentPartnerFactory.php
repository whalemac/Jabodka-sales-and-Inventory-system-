<?php

namespace Database\Factories;

use App\Models\ConsignmentPartner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConsignmentPartner>
 */
class ConsignmentPartnerFactory extends Factory
{
    protected $model = ConsignmentPartner::class;

    public function definition(): array
    {
        return [
            'partner_name' => fake()->unique()->company(),
            'contact_details' => fake()->phoneNumber(),
        ];
    }
}
