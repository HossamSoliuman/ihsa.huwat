<?php

namespace Database\Factories;

use App\Models\LookupList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Shared shape for the option lists. Each list keeps its own factory so tests read in
 * the language of the list they exercise; only the made-up name differs between them.
 *
 * @extends Factory<LookupList>
 */
abstract class LookupListFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('option_????####'),
            'name' => $this->namePrefix().' '.fake()->unique()->numerify('###'),
            'sort_order' => fake()->numberBetween(1, 90) * 10,
            'is_active' => true,
        ];
    }

    /** Switched off: kept for the submissions that already reference it, hidden from the form. */
    public function retired(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }

    /** Arabic stem the generated name is built from. */
    abstract protected function namePrefix(): string;
}
