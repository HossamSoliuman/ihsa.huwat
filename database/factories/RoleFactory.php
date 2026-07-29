<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Role> */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        $code = Str::lower(fake()->unique()->lexify('role_??????'));

        return ['code' => $code, 'name_ar' => fake()->jobTitle(), 'dashboard_route' => 'dashboard.admin'];
    }

    public function superAdmin(): static
    {
        return $this->state(fn (): array => [
            'code' => 'super_admin',
            'name_ar' => 'الإدارة العليا',
            'dashboard_route' => 'dashboard.admin',
        ]);
    }
}
