<?php

namespace Database\Factories;

use App\Models\HarborLicense;
use App\Models\Port;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HarborLicense>
 */
class HarborLicenseFactory extends Factory
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
            'license_number' => fake()->unique()->bothify('LIC-####-??'),
            'license_type' => 'seasonal',
            'license_holder_name' => fake()->name(),
            'boat_number' => fake()->bothify('B-####'),
            'issue_date' => today(),
            'expiry_date' => today()->addYear(),
            'license_status' => 'valid',
        ];
    }
}
