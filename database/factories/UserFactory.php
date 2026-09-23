<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
<<<<<<< HEAD
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username'       => fake()->unique()->userName(),
            'password'       => static::$password ??= Hash::make('password'),
            'role'           => fake()->randomElement(['admin', 'staff']),
=======
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
>>>>>>> e30c199068b93b642068a39e0e94a172dda70cf0
            'remember_token' => Str::random(10),
        ];
    }

<<<<<<< HEAD
    public function superAdmin(): static
    {
        return $this->state(fn () => ['role' => 'super_admin']);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function staff(): static
    {
        return $this->state(fn () => ['role' => 'staff']);
=======
    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
>>>>>>> e30c199068b93b642068a39e0e94a172dda70cf0
    }
}
