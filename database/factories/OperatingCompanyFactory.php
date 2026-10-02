<?php

namespace Database\Factories;

use App\Models\OperatingCompany;
use App\Models\Port;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperatingCompany>
 */
class OperatingCompanyFactory extends Factory
{
    protected $model = OperatingCompany::class;

    public function definition(): array
    {
        return [
            'name' => 'شركة '.fake()->unique()->numerify('تشغيل ###'),
            'commercial_register' => fake()->unique()->numerify('10########'),
            'phone' => '05'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'contact_name' => fake()->name(),
            'status' => OperatingCompany::ACTIVE,
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => OperatingCompany::SUSPENDED]);
    }

    /**
     * يُسند الموانئ إلى الشركة بعد إنشائها.
     */
    public function operating(Port ...$ports): static
    {
        return $this->afterCreating(function (OperatingCompany $company) use ($ports) {
            $company->ports()->attach(collect($ports)->pluck('id'), ['started_at' => today()]);
        });
    }
}
