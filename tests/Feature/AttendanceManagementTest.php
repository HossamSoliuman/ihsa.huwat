<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Port;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unrelated_role_cannot_open_attendance_dashboard(): void
    {
        $user = User::factory()->create(['role_id' => Role::query()->where('code', 'quality_supervisor')->value('id')]);

        $this->actingAs($user)->get(route('dashboard.attendance.index'))->assertForbidden();
    }

    public function test_port_supervisor_only_sees_their_daily_roster(): void
    {
        $port = Port::factory()->create();
        $otherPort = Port::factory()->create();
        $user = $this->portSupervisor($port);
        $visible = Employee::factory()->create();
        $hidden = Employee::factory()->create();
        EmployeeAssignment::factory()->create(['employee_id' => $visible->id, 'port_id' => $port->id]);
        EmployeeAssignment::factory()->create(['employee_id' => $hidden->id, 'port_id' => $otherPort->id]);

        $this->actingAs($user)->get(route('dashboard.attendance.index', ['date' => today()->toDateString()]))
            ->assertOk()
            ->assertSee($visible->user->full_name)
            ->assertDontSee($hidden->user->full_name);
    }

    public function test_check_in_and_check_out_update_one_attendance_record(): void
    {
        $this->travelTo(today()->setTime(6, 10));
        $user = $this->hrManager();
        $assignment = EmployeeAssignment::factory()->create([
            'shift_id' => Shift::query()->where('name', 'morning')->value('id'),
        ]);

        $this->actingAs($user)->post(route('dashboard.attendance.check-in', $assignment))->assertSessionHasNoErrors();
        $attendance = Attendance::query()->firstOrFail();
        $this->assertSame('present', $attendance->status);
        $this->assertNotNull($attendance->check_in);

        $this->travel(8)->hours();
        $this->actingAs($user)->post(route('dashboard.attendance.check-out', $assignment))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('attendance', 1);
        $this->assertNotNull($attendance->fresh()->check_out);
    }

    public function test_absence_replaces_existing_times_atomically(): void
    {
        $user = $this->hrManager();
        $assignment = EmployeeAssignment::factory()->create();
        Attendance::factory()->create([
            'employee_id' => $assignment->employee_id,
            'shift_id' => $assignment->shift_id,
            'check_in' => now()->subHour(),
            'check_out' => now(),
        ]);

        $this->actingAs($user)->post(route('dashboard.attendance.absence', $assignment))->assertSessionHasNoErrors();
        $attendance = Attendance::query()->firstOrFail();

        $this->assertSame('absent', $attendance->status);
        $this->assertNull($attendance->check_in);
        $this->assertNull($attendance->check_out);
    }

    public function test_shift_cannot_change_after_attendance_is_recorded(): void
    {
        $user = $this->hrManager();
        $assignment = EmployeeAssignment::factory()->create();
        Attendance::factory()->create([
            'employee_id' => $assignment->employee_id,
            'shift_id' => $assignment->shift_id,
        ]);
        $otherShift = Shift::query()->whereKeyNot($assignment->shift_id)->firstOrFail();

        $this->actingAs($user)->patch(route('dashboard.attendance.shift.update', $assignment), ['shift_id' => $otherShift->id])
            ->assertSessionHasErrors('shift_id');
        $this->assertSame($assignment->shift_id, $assignment->fresh()->shift_id);
    }

    public function test_port_supervisor_cannot_mutate_another_ports_assignment(): void
    {
        $port = Port::factory()->create();
        $otherPort = Port::factory()->create();
        $user = $this->portSupervisor($port);
        $assignment = EmployeeAssignment::factory()->create(['port_id' => $otherPort->id]);

        $this->actingAs($user)->post(route('dashboard.attendance.check-in', $assignment))->assertForbidden();
        $this->assertDatabaseCount('attendance', 0);
    }

    public function test_substitute_assignment_is_temporary_and_cannot_duplicate_employee_day(): void
    {
        $user = $this->hrManager();
        $port = Port::factory()->create();
        $employee = Employee::factory()->create();
        $shift = Shift::query()->firstOrFail();
        $payload = ['date' => today()->toDateString(), 'port_id' => $port->id, 'shift_id' => $shift->id, 'employee_id' => $employee->id];

        $this->actingAs($user)->post(route('dashboard.attendance.substitutes.store'), $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employee_assignments', ['employee_id' => $employee->id, 'is_temporary' => true]);

        $this->actingAs($user)->post(route('dashboard.attendance.substitutes.store'), $payload)->assertSessionHasErrors('employee_id');
        $this->assertDatabaseCount('employee_assignments', 1);
    }

    private function hrManager(): User
    {
        return User::factory()->create(['role_id' => Role::query()->where('code', 'hr_manager')->value('id')]);
    }

    private function portSupervisor(Port $port): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('code', 'port_supervisor')->value('id'),
            'port_id' => $port->id,
        ]);
    }
}
