<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class InvoiceFactory extends Factory
{

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'amount' => $this->faker->randomFloat(2, 10000, 300000), 
            'expires_at' => now(),
            'status' => $this->faker->randomElement(['pending', 'paid', 'expired']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

}
