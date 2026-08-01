<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard.admin'))->assertRedirect(route('login'));
    }

    public function test_only_super_administrators_can_open_the_admin_dashboard(): void
    {
        $hrManager = Role::query()->where('code', 'hr_manager')->firstOrFail();
        $user = User::factory()->create(['role_id' => $hrManager->id]);

        $this->actingAs($user)->get(route('dashboard.admin'))->assertForbidden();
    }

    public function test_super_administrators_see_the_live_dashboard(): void
    {
        $superAdmin = Role::query()->where('code', 'super_admin')->firstOrFail();
        $user = User::factory()->create(['role_id' => $superAdmin->id]);

        $this->actingAs($user)
            ->get(route('dashboard.admin'))
            ->assertOk()
            ->assertSee('إحصاء المصيد')
            ->assertSee('منصة حوات')
            ->assertSeeInOrder(['نظرة عامة', 'الرئيسية', 'المرافئ', 'العمل اليومي', 'الرحلات'])
            ->assertSeeInOrder(['الإدارة', 'الموظفون', 'الحضور', 'الرواتب'])
            ->assertSee('data-icon="home"', false)
            ->assertSee('data-icon="ship"', false)
            ->assertDontSee(route('dashboard.region-overview.index'), false)
            ->assertDontSee(route('dashboard.governorate-overview.index'), false)
            ->assertDontSee(route('dashboard.port-operations.index'), false)
            ->assertSee('إنتاج المناطق');
    }
}
