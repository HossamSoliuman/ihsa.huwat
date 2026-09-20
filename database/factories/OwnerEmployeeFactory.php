<?php

namespace Database\Factories;

use App\Models\JobTitle;
use App\Models\OwnerEmployee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OwnerEmployee>
 */
class OwnerEmployeeFactory extends Factory
{
    protected $model = OwnerEmployee::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory()->owner(),
            'job_title_id' => JobTitle::factory(),
            'name' => fake()->name(),
            'phone' => '05'.fake()->numerify('########'),
            'nationality' => 'سعودي',
            'id_number' => fake()->numerify('1#########'),
            'status' => 'نشط',
        ];
    }
}
