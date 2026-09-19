<?php

namespace Database\Factories;

use App\Models\Role;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        $key = fake()->unique()->lexify('role_??????');

        return [
            'key' => $key,
            'name' => 'دور '.$key,
            'name_en' => ucfirst($key),
            'description' => null,
            'has_portal' => true,
            'display_order' => 0,
            'active' => true,
        ];
    }

    /**
     * دور من أدوار التطبيق الثابتة بمفتاحه المعروف.
     */
    public function key(string $key): static
    {
        return $this->state(fn () => ['key' => $key] + (RoleSeeder::ROLES[$key] ?? []));
    }
}
