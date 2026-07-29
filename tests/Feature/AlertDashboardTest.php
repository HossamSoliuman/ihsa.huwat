<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Trip;
use App\Models\TripAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AlertDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_and_unrelated_role_is_forbidden(): void
    {
        $this->get(route('dashboard.alerts.index'))->assertRedirect(route('login'));

        $user = User::factory()->create([
            'role_id' => Role::query()->where('code', 'hr_manager')->value('id'),
        ]);

        $this->actingAs($user)->get(route('dashboard.alerts.index'))->assertForbidden();
    }

    public function test_governorate_supervisor_only_sees_alerts_from_their_governorate(): void
    {
        $governorate = Governorate::factory()->create();
        $otherGovernorate = Governorate::factory()->create();
        $port = Port::factory()->create(['governorate_id' => $governorate->id, 'name' => 'ميناء النطاق']);
        $otherPort = Port::factory()->create(['governorate_id' => $otherGovernorate->id, 'name' => 'ميناء خارج النطاق']);
        $user = User::factory()->create([
            'role_id' => Role::query()->where('code', 'gov_supervisor')->value('id'),
            'governorate_id' => $governorate->id,
        ]);
        $visibleTrip = Trip::factory()->create(['port_id' => $port->id, 'trip_code' => 'VISIBLE-TRIP', 'status' => 'arrived', 'actual_arrival' => now()->subMinutes(20)]);
        $hiddenTrip = Trip::factory()->create(['port_id' => $otherPort->id, 'trip_code' => 'HIDDEN-TRIP', 'status' => 'arrived', 'actual_arrival' => now()->subMinutes(20)]);

        $this->actingAs($user)->get(route('dashboard.alerts.index'))
            ->assertOk()
            ->assertSee($visibleTrip->trip_code)
            ->assertDontSee($hiddenTrip->trip_code)
            ->assertSee('1');
    }

    public function test_dashboard_builds_waiting_and_documentation_alerts_from_live_data(): void
    {
        $port = Port::factory()->create(['name' => 'ميناء المراقبة']);
        $user = $this->superAdmin();
        $employee = Employee::factory()->create();
        EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'port_id' => $port->id]);
        $waiting = Trip::factory()->create(['port_id' => $port->id, 'status' => 'arrived', 'actual_arrival' => now()->subMinutes(20)]);
        $approved = Trip::factory()->create([
            'port_id' => $port->id,
            'status' => 'approved',
            'approved_at' => now(),
            'edited_after_approval' => true,
        ]);

        $this->actingAs($user)->get(route('dashboard.alerts.index'))
            ->assertOk()
            ->assertSee($waiting->trip_code)
            ->assertSee('قارب وصل ولم يبدأ إحصاؤه')
            ->assertSee('صورة الميزان غير مرفقة')
            ->assertSee('توقيع الكابتن غير موجود')
            ->assertSee('تعديل بيانات بعد الاعتماد')
            ->assertSee($approved->trip_code);
    }

    public function test_complete_trip_attachments_suppress_documentation_alerts(): void
    {
        $port = Port::factory()->create();
        $user = $this->superAdmin();
        $employee = Employee::factory()->create();
        EmployeeAssignment::factory()->create(['employee_id' => $employee->id, 'port_id' => $port->id]);
        $trip = Trip::factory()->create(['port_id' => $port->id, 'status' => 'approved', 'approved_at' => now()]);
        TripAttachment::factory()->create(['trip_id' => $trip->id, 'type' => 'scale_photo']);
        TripAttachment::factory()->create(['trip_id' => $trip->id, 'type' => 'captain_signature']);

        $this->actingAs($user)->get(route('dashboard.alerts.index'))
            ->assertOk()
            ->assertDontSee('صورة الميزان غير مرفقة')
            ->assertDontSee('توقيع الكابتن غير موجود');
    }

    public function test_temporary_shift_coverage_resolves_absent_employee_alert(): void
    {
        $port = Port::factory()->create();
        $user = $this->superAdmin();
        $shift = Shift::query()->where('name', 'morning')->firstOrFail();
        $employee = Employee::factory()->create();
        EmployeeAssignment::factory()->create([
            'employee_id' => $employee->id,
            'port_id' => $port->id,
            'shift_id' => $shift->id,
        ]);
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
            'status' => 'absent',
        ]);

        $this->actingAs($user)->get(route('dashboard.alerts.index'))
            ->assertOk()
            ->assertSee('موظف غائب دون بديل');

        EmployeeAssignment::factory()->create([
            'port_id' => $port->id,
            'shift_id' => $shift->id,
            'is_temporary' => true,
        ]);

        $this->actingAs($user)->get(route('dashboard.alerts.index'))
            ->assertOk()
            ->assertDontSee('موظف غائب دون بديل');
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('code', 'super_admin')->value('id'),
        ]);
    }
}
