<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'admin_id' => User::factory(),
            'target_user_id' => User::factory(),
            'action' => fake()->randomElement(['user.block', 'user.update', 'user.delete']),
            'details' => ['demo' => true],
            'ip' => fake()->ipv4(),
        ];
    }
}
