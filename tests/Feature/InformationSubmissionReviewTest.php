<?php

namespace Tests\Feature;

use App\Models\InformationSubmission;
use App\Models\Port;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InformationSubmissionReviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_away_from_the_review_desk(): void
    {
        $this->get(route('information.admin.index'))->assertRedirect(route('login'));
    }

    public function test_unauthorised_roles_cannot_open_the_review_desk(): void
    {
        $this->actingAs($this->userWithRole('hr_manager'))
            ->get(route('information.admin.index'))
            ->assertForbidden();
    }

    public function test_dashboard_lists_submissions_with_status_totals(): void
    {
        $reviewer = $this->reviewer();
        $submitted = InformationSubmission::factory()->create(['owner_full_name' => 'عبدالله أحمد البحري']);
        InformationSubmission::factory()->status('approved', $reviewer)->create();
        InformationSubmission::factory()->status('rejected', $reviewer, 'مستندات ناقصة')->create();

        $this->actingAs($reviewer)
            ->get(route('information.admin.index'))
            ->assertOk()
            ->assertSee('الطلبات الواردة')
            ->assertSee($submitted->reference_no)
            ->assertSee('عبدالله أحمد البحري')
            ->assertSee('معتمد')
            ->assertSee('مرفوض')
            ->assertViewHas('statusCounts', fn (array $counts): bool => $counts['all'] === 3
                && $counts['submitted'] === 1
                && $counts['approved'] === 1
                && $counts['rejected'] === 1);
    }

    public function test_dashboard_filters_by_status_port_and_search_term(): void
    {
        $reviewer = $this->reviewer();
        $port = Port::factory()->create();
        $match = InformationSubmission::factory()->status('needs_edit', $reviewer, 'أرفق رخصة سارية')->create([
            'port_id' => $port->id,
            'owner_full_name' => 'سالم علي الصياد',
        ]);
        $other = InformationSubmission::factory()->create(['owner_full_name' => 'ناصر حسن البحار']);

        $this->actingAs($reviewer)
            ->get(route('information.admin.index', ['status' => 'needs_edit', 'port_id' => $port->id, 'q' => 'سالم']))
            ->assertOk()
            ->assertSee($match->reference_no)
            ->assertDontSee($other->reference_no);
    }

    public function test_dashboard_rejects_an_unknown_status_filter(): void
    {
        $this->actingAs($this->reviewer())
            ->get(route('information.admin.index', ['status' => 'archived']))
            ->assertInvalid(['status']);
    }

    public function test_review_page_shows_every_section_of_the_submission(): void
    {
        $submission = InformationSubmission::factory()->create();

        $this->actingAs($this->reviewer())
            ->get(route('information.admin.show', $submission))
            ->assertOk()
            ->assertSee($submission->reference_no)
            ->assertSee('بيانات المالك')
            ->assertSee('أدوات الصيد')
            ->assertSee('المستندات والمرفقات')
            ->assertSee('سجل النشاط')
            ->assertSee('اعتماد الطلب')
            ->assertSee('طلب تعديل')
            ->assertSee('رفض الطلب');
    }

    public function test_reviewer_can_approve_a_submission_and_the_decision_is_recorded(): void
    {
        $reviewer = $this->reviewer();
        $submission = InformationSubmission::factory()->create();

        $this->actingAs($reviewer)
            ->patch(route('information.admin.review', $submission), [
                'status' => 'approved',
                'review_notes' => 'البيانات مكتملة ومطابقة.',
            ])
            ->assertRedirect(route('information.admin.show', $submission));

        $submission->refresh();
        $this->assertSame('approved', $submission->status);
        $this->assertSame($reviewer->getKey(), $submission->reviewed_by);
        $this->assertNotNull($submission->reviewed_at);
        $this->assertSame('البيانات مكتملة ومطابقة.', $submission->review_notes);

        $event = $submission->events()->latest('id')->sole();
        $this->assertSame('status_changed', $event->event_type);
        $this->assertSame('submitted', $event->from_status);
        $this->assertSame('approved', $event->to_status);
        $this->assertSame($reviewer->getKey(), $event->actor_user_id);
    }

    public function test_rejecting_or_requesting_edits_requires_a_note(): void
    {
        $reviewer = $this->reviewer();
        $submission = InformationSubmission::factory()->create();

        $this->actingAs($reviewer)
            ->patch(route('information.admin.review', $submission), ['status' => 'rejected'])
            ->assertInvalid(['review_notes']);

        $this->actingAs($reviewer)
            ->patch(route('information.admin.review', $submission), ['status' => 'needs_edit'])
            ->assertInvalid(['review_notes']);

        $this->assertSame('submitted', $submission->refresh()->status);
        $this->assertSame(0, $submission->events()->count());
    }

    public function test_disallowed_status_transitions_are_rejected(): void
    {
        $reviewer = $this->reviewer();
        $submission = InformationSubmission::factory()->status('approved', $reviewer)->create();

        $this->actingAs($reviewer)
            ->patch(route('information.admin.review', $submission), [
                'status' => 'rejected',
                'review_notes' => 'إلغاء الاعتماد.',
            ])
            ->assertInvalid(['status']);

        $this->assertSame('approved', $submission->refresh()->status);
    }

    public function test_documents_are_streamed_only_when_stored(): void
    {
        Storage::fake('local');
        $reviewer = $this->reviewer();
        Storage::disk('local')->put('information/boat-license.pdf', 'pdf-bytes');
        $submission = InformationSubmission::factory()->create([
            'document_paths' => ['boat_license' => 'information/boat-license.pdf'],
        ]);

        $this->actingAs($reviewer)
            ->get(route('information.admin.documents.show', [$submission, 'boat_license']))
            ->assertOk();

        $this->actingAs($reviewer)
            ->get(route('information.admin.documents.show', [$submission, 'insurance']))
            ->assertNotFound();
    }

    public function test_crew_photos_are_streamed_per_member(): void
    {
        Storage::fake('local');
        $reviewer = $this->reviewer();
        Storage::disk('local')->put('information/crew-one.jpg', 'jpg-bytes');
        $submission = InformationSubmission::factory()->create([
            'crew_members' => [
                ['full_name' => 'سالم علي الصياد', 'photo_path' => 'information/crew-one.jpg'],
                ['full_name' => 'ناصر حسن البحار'],
            ],
        ]);

        $this->actingAs($reviewer)
            ->get(route('information.admin.documents.show', [$submission, 'crew_photo_0']))
            ->assertOk();

        $this->actingAs($reviewer)
            ->get(route('information.admin.documents.show', [$submission, 'crew_photo_1']))
            ->assertNotFound();
    }

    private function reviewer(): User
    {
        return $this->userWithRole('super_admin');
    }

    private function userWithRole(string $code): User
    {
        $role = Role::query()->where('code', $code)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
