<?php

namespace Database\Factories;

use App\Models\Boat;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * مسير فارغ بلا أرقام — الأرقام والسطور يحسبها PayrollService::refresh.
 *
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        return [
            'payroll_number' => fake()->unique()->numerify('PAY-TEST-####'),
            'owner_id' => User::factory()->owner(),
            'boat_name' => fake()->word(),
            'year' => (int) now()->subMonth()->format('Y'),
            'month' => (int) now()->subMonth()->format('n'),
        ];
    }

    public function forBoat(Boat $boat): static
    {
        return $this->state(fn () => ['owner_id' => $boat->owner_id, 'boat_id' => $boat->id, 'boat_name' => $boat->name]);
    }
}
