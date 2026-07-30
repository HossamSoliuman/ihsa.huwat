<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_log_out_from_the_sidebar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_each_role_is_redirected_to_its_native_laravel_dashboard(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);

        foreach (Role::query()->whereNotIn('code', config('government.allowed_roles'))->get() as $role) {
            $user = User::factory()->create([
                'role_id' => $role->id,
                'username' => "user_{$role->code}",
                'password_hash' => Hash::make('password123'),
            ]);

            $this->post(route('login.store'), ['username' => $user->username, 'password' => 'password123'])
                ->assertRedirect(route($role->dashboard_route));
            $this->post(route('logout'));
        }
    }

    public function test_government_user_cannot_log_in_to_the_ihsa_portal(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $role = Role::query()->where('code', 'government_admin')->firstOrFail();
        $user = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'government-only',
            'password_hash' => Hash::make('password123'),
        ]);

        $this->from(route('login'))->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password123',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('username');

        $this->assertGuest('web');
    }
}
