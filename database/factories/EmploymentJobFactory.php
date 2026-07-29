<?php

namespace Database\Factories;

use App\Models\EmploymentJob;
use App\Models\Port;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmploymentJob> */
class EmploymentJobFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference_no' => fake()->unique()->bothify('JOB-####-???'),
            'title_ar' => fake()->jobTitle(),
            'department' => fake()->company(),
            'summary' => fake()->sentence(12),
            'description' => fake()->paragraphs(2, true),
            'responsibilities' => implode("\n", fake()->sentences(3)),
            'requirements' => implode("\n", fake()->sentences(3)),
            'employment_type' => 'full_time',
            'vacancies' => 1,
            'port_id' => Port::factory(),
            'city' => fake()->city(),
            'application_deadline' => today()->addMonth(),
            'status' => 'open',
            'published_at' => now()->subDay(),
            'created_by' => User::factory(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (): array => ['status' => 'closed']);
    }
}
