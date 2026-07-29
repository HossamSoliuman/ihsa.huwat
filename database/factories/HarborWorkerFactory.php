<?php

namespace Database\Factories;

use App\Models\HarborWorker;
use App\Models\Port;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<HarborWorker>
 */
class HarborWorkerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'port_id' => Port::factory(),
            'employee_name' => fake()->name(),
            'identity_number' => Hash::make(fake()->numerify('##########')),
            'nationality' => 'saudi',
            'worker_type' => 'fisherman',
            'mobile_number' => '05'.fake()->numerify('########'),
            'employment_status' => 'active',
            'start_date' => today()->subYear(),
        ];
    }
}
