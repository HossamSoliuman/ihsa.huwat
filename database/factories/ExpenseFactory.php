<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 50, 5000);

        return [
            'expense_number' => 'EXP-'.fake()->unique()->numerify('####-####'),
            'owner_id' => User::factory()->owner(),
            'expense_category_id' => ExpenseCategory::factory(),
            'date' => now()->subDays(fake()->numberBetween(0, 60))->toDateString(),
            'description' => fake()->sentence(3),
            'subtotal' => $amount,
            'discount' => 0,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => $amount,
            'paid_amount' => 0,
        ];
    }

    public function ownedBy(User $owner): static
    {
        return $this->state(fn () => ['owner_id' => $owner->id]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => ['paid_amount' => $attributes['total']]);
    }
}
