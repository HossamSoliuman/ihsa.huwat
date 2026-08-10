<?php

namespace Database\Factories;

use App\Models\Port;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserScope> */
class UserScopeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'scope_type' => UserScope::TYPE_PORT,
            'scope_id' => Port::factory(),
        ];
    }

    public function forRecord(string $scopeType, int $scopeId): static
    {
        return $this->state(fn (): array => ['scope_type' => $scopeType, 'scope_id' => $scopeId]);
    }
}
