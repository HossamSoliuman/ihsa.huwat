<?php

namespace Database\Factories;

use App\Models\InformationDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InformationDraft> */
class InformationDraftFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'payload' => [
                'fields' => ['owner_full_name' => fake()->name()],
                'crew_members' => [['full_name' => fake()->name()]],
                'fishing_tools' => [['type' => 'trawl_net']],
            ],
            'current_step' => fake()->numberBetween(1, 6),
        ];
    }
}
