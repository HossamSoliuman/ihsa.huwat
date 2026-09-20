<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'account_user_id' => User::factory()->owner(),
            'customer_type_id' => CustomerType::factory(),
            'name' => fake()->name(),
            'phone' => '05'.fake()->numerify('########'),
            'status' => 'نشط',
        ];
    }

    public function ofAccount(User $account): static
    {
        return $this->state(fn () => ['account_user_id' => $account->id]);
    }
}
