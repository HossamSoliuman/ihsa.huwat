<?php

namespace Database\Factories;

use App\Models\DalalProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DalalProfile>
 */
class DalalProfileFactory extends Factory
{
    protected $model = DalalProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->dalal(),
            'id_number' => fake()->numerify('1#########'),
            'dakka_name' => 'دكة '.fake()->word(),
            'dakka_number' => fake()->numerify('D-###'),
            'company_name' => 'مؤسسة '.fake()->company(),
            'cr_number' => fake()->numerify('10########'),
            'vat_number' => fake()->numerify('3##############'),
        ];
    }
}
