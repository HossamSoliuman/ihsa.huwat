<?php

namespace Database\Factories;

use App\Models\Port;
use App\Models\StatisticsOfficer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatisticsOfficer>
 */
class StatisticsOfficerFactory extends Factory
{
    protected $model = StatisticsOfficer::class;

    public function definition(): array
    {
        return [
            'port_id' => Port::factory(),
            'name' => fake()->name(),
            'employee_number' => 'SO-'.fake()->unique()->numerify('###'),
            'shift' => fake()->randomElement(['صباحية', 'مسائية']),
            'status' => 'نشط',
        ];
    }

    /**
     * سجلّ موظف مربوط بحساب عدّاد يدخل التطبيق وبوابته.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
        ]);
    }

    public function atPort(Port $port): static
    {
        return $this->state(fn () => ['port_id' => $port->id]);
    }
}
