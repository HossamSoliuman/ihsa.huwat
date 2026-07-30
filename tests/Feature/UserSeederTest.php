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

    public function test_it_seeds_separate_active_ihsa_and_government_administrators_idempotently(): void
    {
        $this->seed(UserSeeder::class);

        $ihsaAdministrator = User::query()
            ->where('username', 'admin')
            ->firstOrFail();
        $governmentAdministrator = User::query()
            ->where('username', 'government_admin')
            ->firstOrFail();

        $this->assertDatabaseCount('users', 2);
        $this->assertSame('super_admin', $ihsaAdministrator->role->code);
        $this->assertSame('admin@example.com', $ihsaAdministrator->email);
        $this->assertTrue($ihsaAdministrator->is_active);
        $this->assertTrue(Hash::check('password', $ihsaAdministrator->password_hash));
        $this->assertSame('government_admin', $governmentAdministrator->role->code);
        $this->assertSame('government@example.com', $governmentAdministrator->email);
        $this->assertTrue($governmentAdministrator->is_active);
        $this->assertTrue(Hash::check('password', $governmentAdministrator->password_hash));
    }
}
