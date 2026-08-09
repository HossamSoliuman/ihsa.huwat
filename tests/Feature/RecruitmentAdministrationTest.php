<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EmployeeContract;
use App\Models\EmploymentApplication;
use App\Models\EmploymentApplicationAttachment;
use App\Models\EmploymentJob;
use App\Models\JobTitle;
use App\Models\Port;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RecruitmentAdministrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_recruitment_pages_require_an_authorized_role(): void
    {
        $this->get(route('dashboard.jobs.index'))->assertRedirect(route('login'));

        $role = Role::query()->where('code', 'stat_employee')->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)->get(route('dashboard.jobs.index'))->assertForbidden();
    }

    public function test_hr_manager_can_create_update_and_publish_a_job(): void
    {
        $manager = $this->hrManager();
        $payload = [
            'title_ar' => 'أخصائي إحصاء مصايد',
            'department' => 'البيانات',
            'summary' => 'ملخص واضح للفرصة',
            'description' => 'وصف تفصيلي للفرصة الوظيفية',
            'responsibilities' => 'جمع البيانات ومراجعتها',
            'requirements' => 'درجة البكالوريوس وخبرة مناسبة',
            'employment_type' => 'full_time',
            'vacancies' => 2,
            'city' => 'جدة',
            'salary_min' => 7000,
            'salary_max' => 9000,
            'application_deadline' => today()->addMonth()->format('Y-m-d'),
        ];

        $response = $this->actingAs($manager)->post(route('dashboard.jobs.store'), $payload);
        $job = EmploymentJob::query()->where('title_ar', $payload['title_ar'])->sole();

        $response->assertRedirect(route('dashboard.jobs.edit', $job));
        $this->assertSame('draft', $job->status);
        $this->assertSame($manager->id, $job->created_by);

        $this->actingAs($manager)
            ->patch(route('dashboard.jobs.transition', $job), ['transition' => 'publish'])
            ->assertRedirect(route('dashboard.jobs.index'));

        $this->assertSame('open', $job->fresh()->status);
        $this->assertNotNull($job->fresh()->published_at);
    }

    public function test_hr_manager_can_render_recruitment_lists_and_application_details(): void
    {
        $manager = $this->hrManager();
        $application = EmploymentApplication::factory()->create();

        $this->actingAs($manager)
            ->get(route('dashboard.jobs.index'))
            ->assertOk()
            ->assertSee('إدارة الإعلانات ودورة النشر');

        $this->actingAs($manager)
            ->get(route('dashboard.applications.index'))
            ->assertOk()
            ->assertSee($application->full_name);

        $this->actingAs($manager)
            ->get(route('dashboard.applications.show', $application))
            ->assertOk()
            ->assertSee($application->reference_no)
            ->assertSee('قرار المراجعة');
    }

    public function test_job_lifecycle_rejects_invalid_transitions_and_expired_publication(): void
    {
        $manager = $this->hrManager();
        $job = EmploymentJob::factory()->create([
            'status' => 'draft',
            'published_at' => null,
            'application_deadline' => today()->subDay(),
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->from(route('dashboard.jobs.index'))
            ->patch(route('dashboard.jobs.transition', $job), ['transition' => 'publish'])
            ->assertSessionHasErrors('application_deadline');

        $this->assertSame('draft', $job->fresh()->status);

        $this->actingAs($manager)
            ->patch(route('dashboard.jobs.transition', $job), ['transition' => 'close'])
            ->assertSessionHasErrors('transition');
    }

    public function test_application_review_follows_transition_graph_and_writes_an_audit_event(): void
    {
        $manager = $this->hrManager();
        $application = EmploymentApplication::factory()->create(['status' => 'under_review']);

        $this->actingAs($manager)
            ->patch(route('dashboard.applications.review', $application), [
                'status' => 'accepted',
                'admin_note' => 'تم اعتماد المرشح بعد المقابلة.',
            ])
            ->assertRedirect(route('dashboard.applications.show', $application));

        $application->refresh();
        $this->assertSame('accepted', $application->status);
        $this->assertSame($manager->id, $application->reviewed_by);
        $this->assertNotNull($application->accepted_at);
        $this->assertDatabaseHas('employment_application_events', [
            'application_id' => $application->id,
            'event_type' => 'status_changed',
            'from_status' => 'under_review',
            'to_status' => 'accepted',
            'actor_user_id' => $manager->id,
        ]);
    }

    public function test_authorized_manager_can_download_a_private_attachment(): void
    {
        Storage::fake('local');
        $manager = $this->hrManager();
        $application = EmploymentApplication::factory()->create();
        Storage::disk('local')->put('employment/test/cv.pdf', 'private-file');
        $attachment = EmploymentApplicationAttachment::query()->create([
            'application_id' => $application->id,
            'attachment_type' => 'cv',
            'original_name' => 'resume.pdf',
            'stored_path' => 'employment/test/cv.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 12,
        ]);

        $this->actingAs($manager)
            ->get(route('dashboard.attachments.download', $attachment))
            ->assertOk()
            ->assertDownload('resume.pdf');
    }

    public function test_accepted_application_is_atomically_provisioned_as_an_employee(): void
    {
        $manager = $this->hrManager();
        $port = Port::factory()->create();
        $shift = Shift::query()->firstOrFail();
        $application = EmploymentApplication::factory()->create([
            'status' => 'accepted',
            'accepted_at' => now(),
            'preferred_port_id' => $port->id,
        ]);
        $payload = [
            'username' => 'new.employee',
            'password' => 'Temporary!Pass123',
            'hire_date' => today()->format('Y-m-d'),
            'department_id' => Department::factory()->create()->id,
            'job_title_id' => JobTitle::factory()->create()->id,
            'contract_type' => 'permanent',
            'contract_start_date' => today()->format('Y-m-d'),
            'working_hours_per_day' => 8,
            'working_days_per_week' => 6,
            'base_salary' => 8000,
            'port_id' => $port->id,
            'shift_id' => $shift->id,
        ];

        $this->actingAs($manager)
            ->post(route('dashboard.applications.provision', $application), $payload)
            ->assertRedirect(route('dashboard.applications.show', $application))
            ->assertSessionHas('employment_credentials_once');

        $employeeUser = User::query()->where('username', 'new.employee')->firstOrFail();
        $this->assertTrue(Hash::check($payload['password'], $employeeUser->password_hash));
        $this->assertDatabaseHas('employees', [
            'user_id' => $employeeUser->id,
            'employment_application_id' => $application->id,
        ]);
        $employee = $employeeUser->employee;
        $this->assertMatchesRegularExpression('/^HWT-\d{5}$/', $employee->employee_number);
        $this->assertSame('active', EmployeeContract::query()->where('employee_id', $employee->id)->sole()->status);
        $this->assertDatabaseHas('employee_salary_components', [
            'employee_id' => $employee->id,
            'amount' => 8000,
        ]);
        $this->assertDatabaseHas('employee_assignments', [
            'port_id' => $port->id,
            'shift_id' => $shift->id,
        ]);
        $this->assertSame('account_created', $application->fresh()->status);
        $this->assertSame($employeeUser->id, $application->fresh()->employee_user_id);
    }

    private function hrManager(): User
    {
        $role = Role::query()->where('code', 'hr_manager')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
