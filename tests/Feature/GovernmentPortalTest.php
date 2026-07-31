<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GovernmentPortalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_government_login_is_a_separate_screen(): void
    {
        $this->get(route('government.login'))
            ->assertOk()
            ->assertSeeInOrder(['البوابة الحكومية', 'اسم المستخدم', 'كلمة المرور', 'دخول'])
            ->assertDontSee('SECURE DECISION GATEWAY')
            ->assertDontSee('بوابة منفصلة عن جلسة النظام الإداري')
            ->assertSee(asset('assets/img/hud/hawat-logo.png'), false)
            ->assertSee(route('government.login.store'), false);
    }

    public function test_government_guests_are_redirected_to_the_government_login(): void
    {
        $this->get('/gov')->assertRedirect(route('government.login'));
        $this->get(route('government.dashboard'))->assertRedirect(route('government.login'));
        $this->get(route('government.seasons.index'))->assertRedirect(route('government.login'));
    }

    public function test_main_ihsa_session_does_not_authenticate_the_government_portal(): void
    {
        $this->actingAs($this->ihsaAdministrator())
            ->get(route('government.dashboard'))
            ->assertRedirect(route('government.login'));

        $this->assertGuest('government');
    }

    public function test_authorized_user_can_start_a_separate_government_session(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $administrator = $this->governmentAdministrator([
            'username' => 'government-admin',
            'password_hash' => Hash::make('secure-password'),
        ]);

        $this->post(route('government.login.store'), [
            'username' => $administrator->username,
            'password' => 'secure-password',
        ])->assertRedirect(route('government.dashboard'));

        $this->assertAuthenticatedAs($administrator, 'government');
        $this->assertGuest('web');
    }

    public function test_ihsa_administrator_credentials_are_rejected_by_the_government_login(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $administrator = $this->ihsaAdministrator([
            'username' => 'government-denied',
            'password_hash' => Hash::make('secure-password'),
        ]);

        $this->from(route('government.login'))->post(route('government.login.store'), [
            'username' => $administrator->username,
            'password' => 'secure-password',
        ])->assertRedirect(route('government.login'))->assertSessionHasErrors('username');

        $this->assertGuest('government');
    }

    public function test_only_authorized_roles_can_use_an_existing_government_session(): void
    {
        $this->actingAs($this->ihsaAdministrator(), 'government')
            ->get(route('government.dashboard'))
            ->assertForbidden();
    }

    public function test_government_root_redirects_to_the_government_dashboard(): void
    {
        $this->actingAs($this->governmentAdministrator(), 'government')
            ->get('/gov')
            ->assertRedirect(route('government.dashboard'));
    }

    public function test_administrator_sees_the_isolated_government_dashboard(): void
    {
        $region = Region::factory()->create(['name' => 'منطقة لوحة الحكومة']);
        Season::factory()->for($region)->create([
            'name' => 'موسم اللوحة النشط',
            'status' => Season::STATUS_ACTIVE,
            'licenses_count' => 45,
        ]);

        $this->actingAs($this->governmentAdministrator(), 'government')
            ->get(route('government.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['البوابة الحكومية', 'لوحة التحكم الحكومية', 'المواسم النشطة', 'أحدث المواسم'])
            ->assertSee('جلسة حكومية مستقلة')
            ->assertSee('موسم اللوحة النشط')
            ->assertSee('data-icon="landmark"', false)
            ->assertSee('data-icon="calendar"', false)
            ->assertDontSee('طلبات التوظيف');
    }

    public function test_administrator_can_filter_the_season_registry(): void
    {
        $region = Region::factory()->create(['name' => 'المنطقة المستهدفة']);
        $otherRegion = Region::factory()->create(['name' => 'منطقة أخرى']);
        Season::factory()->for($region)->create(['name' => 'موسم الروبيان المستهدف', 'status' => Season::STATUS_ACTIVE]);
        Season::factory()->for($otherRegion)->create(['name' => 'موسم لا يظهر', 'status' => Season::STATUS_CLOSED]);

        $this->actingAs($this->governmentAdministrator(), 'government')
            ->get(route('government.seasons.index', [
                'search' => 'الروبيان',
                'status' => Season::STATUS_ACTIVE,
                'region_id' => $region->id,
            ]))
            ->assertOk()
            ->assertSee('موسم الروبيان المستهدف')
            ->assertDontSee('موسم لا يظهر');
    }

    public function test_administrator_can_create_a_fishing_season(): void
    {
        $region = Region::factory()->create();
        $administrator = $this->governmentAdministrator();

        $this->actingAs($administrator, 'government')
            ->get(route('government.seasons.create'))
            ->assertOk()
            ->assertSeeInOrder(['إنشاء موسم صيد جديد', 'بيانات الموسم', 'أدوات الصيد المسموحة']);

        $this->actingAs($administrator, 'government')
            ->post(route('government.seasons.store'), [
                'name' => 'موسم صيد الكنعد',
                'status' => Season::STATUS_UPCOMING,
                'region_id' => $region->id,
                'start_date' => '2026-08-01',
                'end_date' => '2026-09-30',
                'fishing_tools' => [config('government.fishing_tool_options.0')],
                'licenses_count' => 25,
                'minimum_size' => 10.5,
                'maximum_size' => 25,
                'restrictions' => 'الالتزام بمناطق الإنزال المعتمدة.',
            ])
            ->assertRedirect(route('government.seasons.index'))
            ->assertSessionHasNoErrors();

        $season = Season::query()->where('name', 'موسم صيد الكنعد')->firstOrFail();

        $this->assertModelExists($season);
        $this->assertSame($region->id, $season->region_id);
        $this->assertSame(25, $season->licenses_count);
        $this->assertSame([config('government.fishing_tool_options.0')], $season->fishing_tools);
    }

    public function test_season_measurements_and_dates_are_validated(): void
    {
        $region = Region::factory()->create();

        $this->actingAs($this->governmentAdministrator(), 'government')
            ->from(route('government.seasons.create'))
            ->post(route('government.seasons.store'), [
                'name' => 'موسم غير صالح',
                'status' => Season::STATUS_ACTIVE,
                'region_id' => $region->id,
                'start_date' => '2026-09-30',
                'end_date' => '2026-08-01',
                'fishing_tools' => [config('government.fishing_tool_options.0')],
                'licenses_count' => 2,
                'minimum_size' => 30,
                'maximum_size' => 20,
                'restrictions' => 'قيود الموسم.',
            ])
            ->assertRedirect(route('government.seasons.create'))
            ->assertSessionHasErrors(['end_date', 'maximum_size']);
    }

    public function test_regions_linked_to_seasons_cannot_be_deleted(): void
    {
        $region = Region::factory()->create();
        Season::factory()->for($region)->create();

        $this->actingAs($this->ihsaAdministrator())
            ->from(route('dashboard.master-data.index', ['section' => 'regions']))
            ->delete(route('dashboard.regions.destroy', $region))
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($region);
    }

    public function test_government_logout_does_not_end_the_main_ihsa_session(): void
    {
        $ihsaAdministrator = $this->ihsaAdministrator();
        $governmentAdministrator = $this->governmentAdministrator();
        $this->actingAs($ihsaAdministrator, 'web');
        Auth::guard('government')->login($governmentAdministrator);

        $this->post(route('government.logout'))->assertRedirect(route('government.login'));

        $this->assertAuthenticatedAs($ihsaAdministrator, 'web');
        $this->assertGuest('government');
    }

    public function test_unrequested_government_modules_are_not_exposed(): void
    {
        $this->actingAs($this->governmentAdministrator(), 'government')->get('/gov/fishing-tools')->assertNotFound();
        $this->actingAs($this->governmentAdministrator(), 'government')->get('/gov/production')->assertNotFound();
    }

    /** @param array<string, mixed> $attributes */
    private function governmentAdministrator(array $attributes = []): User
    {
        $role = Role::query()->where('code', 'government_admin')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id, ...$attributes]);
    }

    /** @param array<string, mixed> $attributes */
    private function ihsaAdministrator(array $attributes = []): User
    {
        $role = Role::query()->where('code', 'super_admin')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id, ...$attributes]);
    }
}
