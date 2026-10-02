<?php

namespace Database\Factories;

use App\Models\HiringRound;
use App\Models\OperatingCompany;
use App\Models\Port;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HiringRound>
 */
class HiringRoundFactory extends Factory
{
    protected $model = HiringRound::class;

    public function definition(): array
    {
        return [
            'operating_company_id' => OperatingCompany::factory(),
            'port_id' => Port::factory(),
            'title' => 'توظيف عدّادين '.fake()->numerify('###'),
            'seats' => 3,
            'opens_at' => today()->subDays(2),
            'closes_at' => today()->addDays(20),
            'status' => HiringRound::OPEN,
        ];
    }

    public function atPortOf(OperatingCompany $company, Port $port): static
    {
        return $this->state(fn () => ['operating_company_id' => $company->id, 'port_id' => $port->id]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => HiringRound::DRAFT]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => HiringRound::CLOSED]);
    }
}
