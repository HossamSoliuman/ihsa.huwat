<?php

namespace Database\Factories;

use App\Models\Payroll;
use App\Models\PayrollLine;
use App\Models\PayType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollLine>
 */
class PayrollLineFactory extends Factory
{
    protected $model = PayrollLine::class;

    public function definition(): array
    {
        return [
            'payroll_id' => Payroll::factory(),
            'member_name' => fake()->name(),
            'pay_type_id' => fn () => PayType::named(PayType::SHARE)->id,
            'profit_shares' => 1,
        ];
    }
}
