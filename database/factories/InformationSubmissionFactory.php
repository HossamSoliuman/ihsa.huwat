<?php

namespace Database\Factories;

use App\Models\Boat;
use App\Models\Captain;
use App\Models\InformationSubmission;
use App\Models\Port;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<InformationSubmission> */
class InformationSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'reference_no' => 'INFO-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'status' => InformationSubmission::STATUS_SUBMITTED,
            'submitted_by' => User::factory(),
            'port_id' => Port::factory(),
            'boat_id' => Boat::factory(),
            'captain_id' => Captain::factory(),
            'owner_full_name' => fake()->name(),
            'owner_national_id' => '1'.fake()->unique()->numerify('#########'),
            'owner_nationality' => 'saudi',
            'owner_birth_date' => fake()->dateTimeBetween('-70 years', '-18 years'),
            'owner_email' => fake()->safeEmail(),
            'owner_phone' => '05'.fake()->numerify('########'),
            'owner_region' => fake()->city().' الإدارية',
            'owner_governorate' => fake()->city(),
            'owner_city' => fake()->city(),
            'owner_address' => fake()->streetAddress(),
            'crew_count' => 1,
            'fishing_method' => fake()->randomElement(array_keys(config('information.fishing_methods'))),
            'license_number' => fake()->unique()->bothify('LIC-####-??'),
            'license_issue_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'license_expiry_date' => fake()->dateTimeBetween('+1 month', '+3 years'),
            'boat_data' => [
                'boat_name' => fake()->word(),
                'boat_name_en' => fake()->word(),
                'registration_no' => fake()->unique()->bothify('B-####'),
                'boat_type' => 'small',
                'boat_classification' => 'traditional_craft',
                'hull_material' => 'wood_fiberglass',
                'boat_build_date' => now()->subYears(3)->format('Y-m-d'),
                'boat_license_expiry_date' => now()->addYear()->format('Y-m-d'),
                'hull_number' => fake()->unique()->bothify('HULL-####'),
                'engine_number' => fake()->unique()->bothify('ENG-####'),
                'engine_serial_number' => fake()->unique()->bothify('SER-####'),
                'call_sign' => fake()->unique()->bothify('HZ-###'),
                'berth_number' => fake()->unique()->numerify('##'),
                'mooring_number' => fake()->unique()->bothify('B-##'),
            ],
            'captain_data' => [
                'captain_full_name' => fake()->name(),
                'captain_national_id' => '1'.fake()->unique()->numerify('#########'),
                'captain_phone' => '05'.fake()->numerify('########'),
                'captain_license_number' => fake()->unique()->bothify('MAR-####'),
                'captain_license_expiry_date' => now()->addYears(2)->format('Y-m-d'),
                'captain_fishing_license_number' => fake()->unique()->bothify('FSH-####'),
                'captain_fishing_license_issue_date' => now()->subYear()->format('Y-m-d'),
                'captain_fishing_license_expiry_date' => now()->addYears(2)->format('Y-m-d'),
                'captain_nationality' => 'saudi',
            ],
            'crew_members' => [[
                'full_name' => fake()->name(),
                'identity_number' => '2'.fake()->unique()->numerify('#########'),
                'phone' => '05'.fake()->numerify('########'),
                'nationality' => 'saudi',
                'role' => 'fisher',
                'fishing_license_number' => fake()->unique()->bothify('FSH-####'),
                'fishing_license_issue_date' => now()->subYear()->format('Y-m-d'),
                'fishing_license_expiry_date' => now()->addYears(2)->format('Y-m-d'),
            ]],
            'fishing_tools' => [[
                'type' => 'trawl_net',
                'quantity' => 12,
                'size' => '3 إنش',
                'material' => 'nylon',
                'condition' => 'serviceable',
                'is_primary' => true,
            ]],
            'consented_at' => now(),
            'submitted_at' => now(),
        ];
    }

    public function status(string $status, ?User $reviewer = null, ?string $notes = null): static
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'review_notes' => $notes,
            'reviewed_by' => $reviewer?->getKey(),
            'reviewed_at' => now(),
        ]);
    }
}
