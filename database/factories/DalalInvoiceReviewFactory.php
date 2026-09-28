<?php

namespace Database\Factories;

use App\Models\DalalInvoiceReview;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DalalInvoiceReview>
 */
class DalalInvoiceReviewFactory extends Factory
{
    protected $model = DalalInvoiceReview::class;

    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'owner_id' => User::factory()->owner(),
            'dalal_id' => User::factory()->dalal(),
            'status' => DalalInvoiceReview::PENDING,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn () => ['status' => DalalInvoiceReview::ACCEPTED, 'reviewed_at' => now()]);
    }

    public function rejected(string $reason = 'السعر أقل من المتفق عليه'): static
    {
        return $this->state(fn () => ['status' => DalalInvoiceReview::REJECTED, 'reason' => $reason, 'reviewed_at' => now()]);
    }
}
