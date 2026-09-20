<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        return [
            'invoice_number' => now()->format('y-m').'-'.fake()->unique()->numerify('######'),
            'seller_id' => User::factory()->owner(),
            'customer_id' => Customer::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'payment_status_id' => PaymentStatus::factory(),
            'subtotal' => 0,
            'total' => 0,
            'owner_net' => 0,
            'status' => Sale::COMPLETED,
            'sold_at' => now(),
        ];
    }
}
