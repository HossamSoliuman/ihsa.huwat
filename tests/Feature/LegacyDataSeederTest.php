<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmploymentApplication;
use App\Models\User;
use Database\Seeders\LegacyDataSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LegacyDataSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_seeds_the_legacy_dump_with_remapped_relationships_idempotently(): void
    {
        $this->seed();

        $employeeUser = User::query()->where('username', 'KHALID-ALYAQUOBI')->firstOrFail();
        $employee = Employee::query()->where('employee_number', 'EMP-2026-000001')->firstOrFail();
        $application = EmploymentApplication::query()
            ->where('reference_no', 'APP-F69541F80B7FEFE6D044FFE0')
            ->firstOrFail();

        $this->assertDatabaseCount('regions', 6);
        $this->assertDatabaseCount('governorates', 91);
        $this->assertDatabaseCount('ports', 131);
        $this->assertDatabaseCount('harbor_boat_capacities', 393);
        $this->assertDatabaseCount('employment_jobs', 6);
        $this->assertDatabaseCount('employment_application_attachments', 5);
        $this->assertDatabaseCount('employment_application_events', 5);
        $this->assertDatabaseCount('login_attempts', 21);
        $this->assertTrue($employee->user->is($employeeUser));
        $this->assertTrue($employee->employmentApplication->is($application));
        $this->assertTrue($application->employeeUser->is($employeeUser));
        $this->assertSame('employee_portal', $employeeUser->role->code);

        $this->seed(LegacyDataSeeder::class);

        $this->assertDatabaseCount('regions', 6);
        $this->assertDatabaseCount('governorates', 91);
        $this->assertDatabaseCount('ports', 131);
        $this->assertDatabaseCount('harbor_boat_capacities', 393);
        $this->assertDatabaseCount('employment_jobs', 6);
        $this->assertDatabaseCount('employment_applications', 1);
        $this->assertDatabaseCount('employees', 2);
        $this->assertDatabaseCount('login_attempts', 21);
    }
}
