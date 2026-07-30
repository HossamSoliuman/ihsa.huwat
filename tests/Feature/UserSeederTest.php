<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_seeds_an_active_super_administrator_idempotently(): void
    {
        $this->seed(UserSeeder::class);

        $administrator = User::query()
            ->where('username', 'admin')
            ->firstOrFail();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('super_admin', $administrator->role->code);
        $this->assertSame('admin@example.com', $administrator->email);
        $this->assertTrue($administrator->is_active);
        $this->assertTrue(Hash::check('password', $administrator->password_hash));
    }
}
