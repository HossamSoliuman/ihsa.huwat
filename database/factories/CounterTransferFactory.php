<?php

namespace Database\Factories;

use App\Models\CounterTransfer;
use App\Models\Port;
use App\Models\StatisticsOfficer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CounterTransfer>
 */
class CounterTransferFactory extends Factory
{
    protected $model = CounterTransfer::class;

    public function definition(): array
    {
        return [
            'statistics_officer_id' => StatisticsOfficer::factory(),
            'from_port_id' => Port::factory(),
            'to_port_id' => Port::factory(),
            'reason' => null,
        ];
    }
}
