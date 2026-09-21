<?php

namespace Database\Factories;

use App\Models\AppNotification;
use App\Models\NotificationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppNotification>
 */
class AppNotificationFactory extends Factory
{
    protected $model = AppNotification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'notification_type_id' => NotificationType::factory(),
            'title' => fake()->sentence(3),
            'body' => fake()->sentence(8),
            'data' => null,
        ];
    }

    public function to(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()->subHour()]);
    }
}
