<?php

namespace Tests\Feature;

use App\Models\InformationSubmission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InformationStatusTrackerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_portal_opens_on_the_public_identity_gate(): void
    {
        $this->get(route('information.identity.create'))
            ->assertOk()
            ->assertSee('ابدأ بتأكيد بياناتك')
            ->assertSee('رقم الهوية / الإقامة')
            ->assertSee('رقم الجوال');
    }

    public function test_tracker_redirects_to_the_identity_gate_without_a_confirmed_identity(): void
    {
        $this->get(route('information.status.index'))->assertRedirect(route('information.identity.create'));
    }

    public function test_the_gate_rejects_malformed_identity_details(): void
    {
        $this->post(route('information.identity.store'), ['national_id' => '123', 'phone' => 'abc'])
            ->assertInvalid(['national_id', 'phone']);

        $this->get(route('information.status.index'))->assertRedirect(route('information.identity.create'));
    }

    public function test_an_identity_without_submissions_is_sent_to_the_form(): void
    {
        InformationSubmission::factory()->create([
            'owner_national_id' => '1023456789',
            'owner_phone' => '0500000000',
        ]);

        $this->post(route('information.identity.store'), [
            'national_id' => '1023456789',
            'phone' => '0511111111',
        ])->assertRedirect(route('information.create'));
    }

    public function test_an_identity_with_submissions_is_sent_to_the_tracker(): void
    {
        InformationSubmission::factory()->create([
            'owner_national_id' => '1023456789',
            'owner_phone' => '0500000000',
        ]);

        $this->post(route('information.identity.store'), [
            'national_id' => '1023456789',
            'phone' => '0500000000',
        ])->assertRedirect(route('information.status.index'));
    }

    public function test_the_tracker_falls_back_to_the_form_once_the_identity_has_no_records(): void
    {
        $this->withSession(['information_identity' => ['national_id' => '1023456789', 'phone' => '0500000000']])
            ->get(route('information.status.index'))
            ->assertRedirect(route('information.create'));
    }

    public function test_applicant_sees_the_milestone_track_for_a_submission_under_review(): void
    {
        $submission = InformationSubmission::factory()->create([
            'owner_national_id' => '1023456789',
            'owner_phone' => '0500000000',
        ]);
        $submission->events()->create(['event_type' => 'submitted', 'to_status' => 'submitted']);

        $this->post(route('information.identity.store'), [
            'national_id' => '1023456789',
            'phone' => '0500000000',
        ])->assertRedirect(route('information.status.index'));

        $this->get(route('information.status.index'))
            ->assertOk()
            ->assertSee($submission->reference_no)
            ->assertSee('تم إرسال الطلب')
            ->assertSee('تم استلام الطلب')
            ->assertSee('تحت المراجعة')
            ->assertSee('انتظار القرار')
            ->assertViewHas('timeline', function (array $timeline): bool {
                $states = array_column($timeline, 'state', 'key');

                return $states['submitted'] === 'done'
                    && $states['under_review'] === 'current'
                    && $states['decision'] === 'upcoming';
            });
    }

    public function test_a_rejected_submission_shows_its_reason_and_final_state(): void
    {
        $reviewer = $this->reviewer();
        $submission = InformationSubmission::factory()->status('rejected', $reviewer, 'المستندات غير مكتملة.')->create([
            'owner_national_id' => '1023456789',
            'owner_phone' => '0500000000',
        ]);
        $submission->events()->create([
            'event_type' => 'status_changed',
            'from_status' => 'submitted',
            'to_status' => 'rejected',
            'note' => 'المستندات غير مكتملة.',
            'actor_user_id' => $reviewer->getKey(),
        ]);

        $this->post(route('information.identity.store'), [
            'national_id' => '1023456789',
            'phone' => '0500000000',
        ]);

        $this->get(route('information.status.index'))
            ->assertOk()
            ->assertSee('تم رفض الطلب')
            ->assertSee('سبب الرفض')
            ->assertSee('المستندات غير مكتملة.')
            ->assertViewHas('timeline', function (array $timeline): bool {
                $states = array_column($timeline, 'state', 'key');

                return $states['decision'] === 'rejected' && $states['under_review'] === 'done';
            });
    }

    public function test_the_tracker_only_exposes_submissions_of_the_confirmed_identity(): void
    {
        $own = InformationSubmission::factory()->create([
            'owner_national_id' => '1023456789',
            'owner_phone' => '0500000000',
        ]);
        $foreign = InformationSubmission::factory()->create([
            'owner_national_id' => '1099999999',
            'owner_phone' => '0599999999',
        ]);

        $this->post(route('information.identity.store'), [
            'national_id' => '1023456789',
            'phone' => '0500000000',
        ]);

        $this->get(route('information.status.index'))
            ->assertOk()
            ->assertSee($own->reference_no)
            ->assertDontSee($foreign->reference_no);

        $this->get(route('information.status.index', ['reference' => $foreign->reference_no]))
            ->assertNotFound();
    }

    public function test_ending_the_session_clears_the_confirmed_identity(): void
    {
        InformationSubmission::factory()->create([
            'owner_national_id' => '1023456789',
            'owner_phone' => '0500000000',
        ]);

        $this->post(route('information.identity.store'), [
            'national_id' => '1023456789',
            'phone' => '0500000000',
        ]);

        $this->post(route('information.identity.destroy'))->assertRedirect(route('information.identity.create'));
        $this->get(route('information.status.index'))->assertRedirect(route('information.identity.create'));
    }

    public function test_a_confirmed_visitor_is_moved_on_from_the_gate(): void
    {
        InformationSubmission::factory()->create([
            'owner_national_id' => '1023456789',
            'owner_phone' => '0500000000',
        ]);

        $this->post(route('information.identity.store'), [
            'national_id' => '1023456789',
            'phone' => '0500000000',
        ]);

        $this->get(route('information.identity.create'))->assertRedirect(route('information.status.index'));
    }

    private function reviewer(): User
    {
        $role = Role::query()->where('code', 'super_admin')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
