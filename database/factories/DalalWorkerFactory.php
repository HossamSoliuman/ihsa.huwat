<?php

namespace Database\Factories;

use App\Models\DalalWorker;
use App\Models\DalalWorkerType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DalalWorker>
 */
class DalalWorkerFactory extends Factory
{
    protected $model = DalalWorker::class;

    public function definition(): array
    {
        return [
            'dalal_id' => User::factory()->dalal(),
            'dalal_worker_type_id' => DalalWorkerType::factory(),
            'nationality' => fake()->randomElement(['سعودي', 'هندي', 'بنغلاديشي']),
            'count' => fake()->numberBetween(1, 6),
        ];
    }
}
