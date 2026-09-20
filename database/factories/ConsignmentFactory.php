<?php

namespace Database\Factories;

use App\Models\Consignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consignment>
 */
class ConsignmentFactory extends Factory
{
    protected $model = Consignment::class;

    public function definition(): array
    {
        return [
            'consignment_number' => 'CN-'.now()->year.'-'.fake()->unique()->numerify('####'),
            'owner_id' => User::factory()->owner(),
            'dalal_id' => User::factory()->role('dalal'),
            'total_kg' => 0,
            'status' => Consignment::SENT,
            'sent_at' => now(),
        ];
    }
}
