<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\SalaryComponent;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HumanResourcesSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_human_resources_roles_can_open_the_settings_screen(): void
    {
        $this->get(route('dashboard.hr.settings.index'))->assertRedirect(route('login'));

        $this->actingAs($this->userWithRole('finance_officer'))
            ->get(route('dashboard.hr.settings.index'))
            ->assertForbidden();

        $this->actingAs($this->userWithRole('hr_manager'))
            ->get(route('dashboard.hr.settings.index'))
            ->assertOk()
            ->assertSee('إعدادات الموارد البشرية')
            ->assertSee('الأقسام')
            ->assertSee('المسميات الوظيفية')
            ->assertSee('البنوك')
            ->assertSee('أنواع الإجازات')
            ->assertSee('مكوّنات الراتب')
            ->assertSee('المناوبات');
    }

    public function test_the_required_leave_types_salary_components_and_shifts_are_seeded(): void
    {
        $this->assertSame(
            ['annual', 'sick', 'unpaid', 'emergency'],
            LeaveType::query()->ordered()->pluck('code')->all(),
        );

        $this->assertSame(
            ['basic', 'housing', 'transport', 'shift_allowance', 'site_allowance'],
            SalaryComponent::query()->ordered()->pluck('code')->all(),
        );

        $this->assertSame(
            ['morning', 'evening', 'night'],
            Shift::query()->orderBy('id')->pluck('code')->all(),
        );

        $nightShift = Shift::query()->where('code', 'night')->sole();
        $this->assertSame('ليلية', $nightShift->name);
        $this->assertTrue($nightShift->crosses_midnight);
        $this->assertSame(15, $nightShift->grace_minutes);
    }

    public function test_each_settings_section_renders_its_own_records(): void
    {
        $hrManager = $this->userWithRole('hr_manager');

        foreach ([
            'leave_types' => 'إجازة سنوية',
            'salary_components' => 'الراتب الأساسي',
            'shifts' => 'صباحية',
        ] as $section => $expectedRecord) {
            $this->actingAs($hrManager)
                ->get(route('dashboard.hr.settings.index', ['section' => $section]))
                ->assertOk()
                ->assertSee($expectedRecord)
                ->assertDontSee('الموظفون والعقود والإجازات والتكليفات اليومية في مساحة عمل موحدة.');
        }
    }

    public function test_hr_can_add_update_retire_and_delete_a_flat_option(): void
    {
        $hrManager = $this->userWithRole('hr_manager');

        $this->actingAs($hrManager)
            ->post(route('dashboard.hr.settings.options.store', 'departments'), ['name' => 'إدارة التشغيل'])
            ->assertRedirect(route('dashboard.hr.settings.index', ['section' => 'departments']));

        $department = Department::query()->where('name', 'إدارة التشغيل')->sole();
        $this->assertMatchesRegularExpression('/^[a-z0-9_]{2,60}$/', $department->code);

        $this->actingAs($hrManager)
            ->patch(route('dashboard.hr.settings.options.update', ['departments', $department]), [
                'name' => 'إدارة العمليات',
                'sort_order' => 5,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('إدارة العمليات', $department->fresh()->name);
        $this->assertSame(5, $department->fresh()->sort_order);

        $this->actingAs($hrManager)
            ->patch(route('dashboard.hr.settings.options.toggle', ['departments', $department]))
            ->assertSessionHasNoErrors();

        $this->assertFalse($department->fresh()->is_active);

        $this->actingAs($hrManager)
            ->delete(route('dashboard.hr.settings.options.destroy', ['departments', $department]))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($department);
    }

    public function test_leave_behaviour_can_be_updated_without_changing_its_code(): void
    {
        $annualLeave = LeaveType::query()->where('code', 'annual')->sole();

        $this->actingAs($this->userWithRole('hr_manager'))
            ->patch(route('dashboard.hr.settings.leave-types.update', $annualLeave), [
                'name_ar' => 'الإجازة السنوية',
                'is_paid' => '1',
                'annual_days' => '21.0',
                'payroll_effect' => LeaveType::PAYROLL_NONE,
                'sort_order' => 5,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('annual', $annualLeave->fresh()->code);
        $this->assertSame('الإجازة السنوية', $annualLeave->fresh()->name_ar);
        $this->assertSame('21.0', $annualLeave->fresh()->annual_days);
    }

    public function test_the_basic_salary_component_cannot_be_retired_or_changed_to_a_deduction(): void
    {
        $basic = SalaryComponent::query()->where('code', 'basic')->sole();
        $hrManager = $this->userWithRole('hr_manager');

        $this->actingAs($hrManager)
            ->patch(route('dashboard.hr.settings.salary-components.toggle', $basic))
            ->assertForbidden();

        $this->actingAs($hrManager)
            ->patch(route('dashboard.hr.settings.salary-components.update', $basic), [
                'name_ar' => $basic->name_ar,
                'component_type' => SalaryComponent::TYPE_DEDUCTION,
                'calculation_type' => SalaryComponent::CALCULATION_FIXED,
                'sort_order' => $basic->sort_order,
            ])
            ->assertInvalid(['component_type']);

        $this->assertTrue($basic->fresh()->is_active);
        $this->assertSame(SalaryComponent::TYPE_EARNING, $basic->fresh()->component_type);
    }

    public function test_shift_times_set_the_midnight_flag_and_grace_period(): void
    {
        $morning = Shift::query()->where('code', 'morning')->sole();

        $this->actingAs($this->userWithRole('hr_manager'))
            ->patch(route('dashboard.hr.settings.shifts.update', $morning), [
                'name' => 'مناوبة ممتدة',
                'start_time' => '20:00',
                'end_time' => '04:00',
                'grace_minutes' => 12,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('morning', $morning->fresh()->code);
        $this->assertSame('مناوبة ممتدة', $morning->fresh()->name);
        $this->assertTrue($morning->fresh()->crosses_midnight);
        $this->assertSame(12, $morning->fresh()->grace_minutes);
    }

    private function userWithRole(string $code): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('code', $code)->value('id'),
        ]);
    }
}
