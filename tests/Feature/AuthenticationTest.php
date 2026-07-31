<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_uses_hawat_logo(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(asset('assets/img/hud/hawat-logo.png'), false)
            ->assertSee('alt="منصة حوات"', false);
    }

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

    public function test_invalid_dashboard_route_is_handled_without_an_exception_page(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $role = Role::query()->where('code', 'super_admin')->firstOrFail();
        $role->update(['dashboard_route' => 'admin.php']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'username' => 'invalid-dashboard-route',
            'password_hash' => Hash::make('password123'),
        ]);

        $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password123',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('username');

        $this->assertGuest('web');
    }

    public function test_server_errors_do_not_expose_details_when_debugging_is_disabled(): void
    {
        config()->set('app.debug', false);
        Route::get('/test-server-error', fn (): never => throw new RuntimeException('sensitive exception details'));

        $this->get('/test-server-error')
            ->assertInternalServerError()
            ->assertSee('تعذر إكمال الطلب')
            ->assertDontSee('sensitive exception details')
            ->assertDontSee(RuntimeException::class);
    }
}
