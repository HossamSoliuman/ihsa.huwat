<?php

namespace Database\Factories;

use App\Models\EmploymentApplication;
use App\Models\EmploymentJob;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmploymentApplication> */
class EmploymentApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_id' => EmploymentJob::factory(),
            'reference_no' => 'APP-'.strtoupper(bin2hex(random_bytes(12))),
            'status' => EmploymentApplication::STATUS_SUBMITTED,
            'full_name' => fake()->name(),
            'nationality' => 'سعودي',
            'identity_type' => 'national_id',
            'identity_number' => '1'.fake()->unique()->numerify('#########'),
            'birth_date' => fake()->dateTimeBetween('-50 years', '-20 years'),
            'gender' => 'male',
            'marital_status' => 'single',
            'children_count' => 0,
            'mobile' => '05'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'work_type' => 'full_time',
            'source' => 'website',
            'education_level' => 'bachelor',
            'specialization' => fake()->jobTitle(),
            'institution' => fake()->company(),
            'experience_years' => 3,
            'skills' => implode('، ', fake()->words(5)),
            'consent' => true,
            'submitted_at' => now(),
        ];
    }
}
