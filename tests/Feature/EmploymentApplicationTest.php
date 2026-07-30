<?php

namespace Tests\Feature;

use App\Models\EmploymentApplication;
use App\Models\EmploymentJob;
use App\Models\Port;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmploymentApplicationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_open_jobs_are_visible_to_the_public(): void
    {
        $openJob = EmploymentJob::factory()->create(['title_ar' => 'موظف إحصاء']);
        EmploymentJob::factory()->closed()->create(['title_ar' => 'وظيفة مغلقة']);

        $this->get(route('jobs.index'))
            ->assertOk()
            ->assertSee($openJob->title_ar)
            ->assertDontSee('وظيفة مغلقة');
    }

    public function test_public_landing_page_does_not_show_the_navigation_menu(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('employment-public-nav')
            ->assertSee('دخول الموظفين')
            ->assertSee(route('login'), false)
            ->assertDontSee('دخول لوحة التحكم');
    }

    public function test_authenticated_user_sees_their_dashboard_link_on_the_public_landing_page(): void
    {
        $role = Role::query()->where('code', 'super_admin')->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee('دخول لوحة التحكم')
            ->assertSee(route($role->dashboard_route), false)
            ->assertDontSee('دخول الموظفين')
            ->assertDontSee('employment-public-nav');
    }

    public function test_an_applicant_can_submit_a_valid_application_with_private_attachments(): void
    {
        Storage::fake('local');
        $job = EmploymentJob::factory()->create();
        $port = Port::factory()->create();

        $response = $this->post(route('applications.store', $job), [
            ...$this->validApplicationData($port),
            'cv_file' => UploadedFile::fake()->create('cv.pdf', 120, 'application/pdf'),
            'identity_file' => UploadedFile::fake()->create('identity.pdf', 80, 'application/pdf'),
        ]);

        $application = EmploymentApplication::query()->with(['attachments', 'events'])->sole();
        $this->assertModelExists($application);
        $this->assertCount(2, $application->attachments);
        $this->assertCount(1, $application->events);
        Storage::disk('local')->assertExists($application->attachments->first()->stored_path);
        $response->assertRedirect(route('applications.submitted', $application->reference_no));
    }

    public function test_a_cv_and_consent_are_required(): void
    {
        $job = EmploymentJob::factory()->create();
        $port = Port::factory()->create();

        $this->post(route('applications.store', $job), [
            ...$this->validApplicationData($port),
            'consent' => false,
        ])->assertInvalid(['cv_file', 'consent']);

        $this->assertDatabaseEmpty((new EmploymentApplication)->getTable());
    }

    public function test_a_closed_job_cannot_receive_applications(): void
    {
        $job = EmploymentJob::factory()->closed()->create();

        $this->get(route('applications.create', $job))->assertNotFound();
    }

    /** @return array<string, mixed> */
    private function validApplicationData(Port $port): array
    {
        return [
            'full_name' => 'محمد أحمد عبدالله',
            'nationality' => 'سعودي',
            'identity_type' => 'national_id',
            'identity_number' => '1123456789',
            'birth_date' => today()->subYears(30)->format('Y-m-d'),
            'gender' => 'male',
            'marital_status' => 'single',
            'children_count' => 0,
            'mobile' => '0500000000',
            'email' => 'applicant@example.test',
            'city' => 'الرياض',
            'address' => 'حي المروج، شارع البحر',
            'preferred_port_id' => $port->id,
            'work_type' => 'full_time',
            'source' => 'website',
            'education_level' => 'bachelor',
            'specialization' => 'الإحصاء',
            'institution' => 'جامعة الملك سعود',
            'graduation_year' => today()->year - 5,
            'experience_years' => 3,
            'skills' => 'تحليل البيانات، إعداد التقارير',
            'consent' => true,
        ];
    }
}
