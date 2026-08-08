<?php

namespace Tests\Feature;

use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\FishMarketUnit;
use App\Models\FishMarketWorker;
use App\Models\Governorate;
use App\Models\InformationSubmission;
use App\Models\InformationSubmissionEvent;
use App\Models\Port;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InformationDashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('information.admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_unlisted_roles_are_forbidden(): void
    {
        $this->actingAs($this->userWithRole('port_supervisor'))
            ->get(route('information.admin.dashboard'))
            ->assertForbidden();
    }

    public function test_both_supervisory_roles_can_open_the_dashboard(): void
    {
        foreach (['super_admin', 'quality_supervisor'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('information.admin.dashboard'))
                ->assertOk()
                ->assertSee('لوحة مؤشرات مركز المعلومات');
        }
    }

    public function test_counts_respect_the_range_filter(): void
    {
        InformationSubmission::factory()->create(['status' => 'submitted', 'submitted_at' => now()->subDays(2)]);
        InformationSubmission::factory()->create(['status' => 'under_review', 'submitted_at' => now()->subDays(3)]);
        InformationSubmission::factory()->create(['status' => 'approved', 'submitted_at' => now()->subDays(4)]);
        InformationSubmission::factory()->create(['status' => 'rejected', 'submitted_at' => now()->subDays(40)]);

        $this->actingAs($this->reviewer())
            ->get(route('information.admin.dashboard', ['range' => '30']))
            ->assertOk()
            ->assertViewHas('hero', fn (array $hero): bool => $hero['value'] === 3)
            ->assertViewHas('pipeline', function (array $pipeline): bool {
                $counts = collect($pipeline['segments'])->pluck('value', 'label');

                return $counts['جديد'] === 1
                    && $counts['تحت المراجعة'] === 1
                    && $counts['معتمد'] === 1
                    && $counts['مرفوض'] === 0;
            });
    }

    public function test_approval_rate_is_undefined_without_decided_submissions(): void
    {
        InformationSubmission::factory()->create();

        $this->actingAs($this->reviewer())
            ->get(route('information.admin.dashboard'))
            ->assertOk()
            ->assertViewHas('kpis', fn (array $kpis): bool => collect($kpis)->firstWhere('label', 'نسبة الاعتماد')['value'] === null)
            ->assertSee('—');
    }

    public function test_decision_median_uses_the_first_decision_event(): void
    {
        Carbon::setTestNow('2026-08-05 12:00:00');
        $reviewer = $this->reviewer();
        $submission = InformationSubmission::factory()->status('approved', $reviewer)->create([
            'submitted_at' => now()->subDays(10),
            'reviewed_at' => now(),
        ]);
        $this->recordEvent($submission, 'approved', now()->subDays(8), $reviewer);
        $this->recordEvent($submission, 'under_review', now()->subDays(5), $reviewer);
        $this->recordEvent($submission, 'approved', now()->subDay(), $reviewer);

        $this->actingAs($reviewer)
            ->get(route('information.admin.dashboard'))
            ->assertOk()
            ->assertViewHas('kpis', fn (array $kpis): bool => collect($kpis)->firstWhere('label', 'متوسط زمن القرار')['value'] === 2.0);
    }

    public function test_rework_rate_counts_each_submission_once(): void
    {
        $reviewer = $this->reviewer();
        $reworked = InformationSubmission::factory()->status('approved', $reviewer)->create();
        InformationSubmission::factory()->create();
        $this->recordEvent($reworked, 'needs_edit', now()->subDays(2), $reviewer);
        $this->recordEvent($reworked, 'needs_edit', now()->subDay(), $reviewer);

        $this->actingAs($reviewer)
            ->get(route('information.admin.dashboard'))
            ->assertOk()
            ->assertViewHas('kpis', fn (array $kpis): bool => collect($kpis)->firstWhere('label', 'نسبة إعادة الإرسال')['value'] === 50.0);
    }

    public function test_oldest_pending_excludes_decided_submissions(): void
    {
        $reviewer = $this->reviewer();
        InformationSubmission::factory()->status('approved', $reviewer)->create(['submitted_at' => now()->subDays(20)]);
        InformationSubmission::factory()->create(['submitted_at' => now()->subDays(5)]);

        $this->actingAs($reviewer)
            ->get(route('information.admin.dashboard'))
            ->assertOk()
            ->assertViewHas('kpis', fn (array $kpis): bool => collect($kpis)->firstWhere('label', 'أقدم طلب معلّق')['value'] === 5);
    }

    public function test_expired_licenses_are_not_counted_in_the_thirty_day_bucket(): void
    {
        $reviewer = $this->reviewer();
        InformationSubmission::factory()->status('approved', $reviewer)->create(['license_expiry_date' => today()->subDay()]);

        $this->actingAs($reviewer)
            ->get(route('information.admin.dashboard'))
            ->assertOk()
            ->assertViewHas('expiryRisk', fn (array $risk): bool => $risk['expired'] === 1 && $risk['within_30'] === 0);
    }

    public function test_reviewer_metrics_use_the_event_actor(): void
    {
        $recordedReviewer = $this->reviewer();
        $differentReviewer = $this->userWithRole('quality_supervisor');
        $submission = InformationSubmission::factory()->status('approved', $differentReviewer)->create([
            'submitted_at' => now()->subDays(3),
            'reviewed_by' => $differentReviewer->id,
        ]);
        $this->recordEvent($submission, 'approved', now()->subDay(), $recordedReviewer);

        $this->actingAs($recordedReviewer)
            ->get(route('information.admin.dashboard'))
            ->assertOk()
            ->assertViewHas('reviewers', fn (array $reviewers): bool => count($reviewers) === 1
                && $reviewers[0]['name'] === $recordedReviewer->full_name
                && $reviewers[0]['decisions'] === 1);
    }

    public function test_zero_submissions_render_every_empty_state_without_an_exception(): void
    {
        $this->actingAs($this->reviewer())
            ->get(route('information.admin.dashboard'))
            ->assertOk()
            ->assertSee('لا توجد بيانات ضمن الفترة المحددة.')
            ->assertViewHas('hero', fn (array $hero): bool => $hero['value'] === 0);
    }

    public function test_port_filter_narrows_panels_and_survives_drill_through_links(): void
    {
        $port = Port::factory()->create();
        InformationSubmission::factory()->create(['port_id' => $port->id]);
        InformationSubmission::factory()->create();

        $this->actingAs($this->reviewer())
            ->get(route('information.admin.dashboard', ['range' => '7', 'port_id' => $port->id]))
            ->assertOk()
            ->assertViewHas('hero', fn (array $hero): bool => $hero['value'] === 1
                && str_contains($hero['href'], 'range=7')
                && str_contains($hero['href'], 'port_id='.$port->id));
    }

    public function test_census_counts_every_information_registry(): void
    {
        $market = FishMarket::factory()->create();
        $unit = FishMarketUnit::factory()->for($market, 'market')->create();
        FishMarketWorker::factory()->for($unit, 'unit')->create();
        FishMarketBroker::factory()->individual()->for($market, 'market')->create();
        InformationSubmission::factory()->status('approved', $this->reviewer())->create(['crew_count' => 4]);

        $this->actingAs($this->reviewer())
            ->get(route('information.admin.dashboard'))
            ->assertOk()
            ->assertViewHas('census', function (array $census): bool {
                $values = collect($census)->pluck('value', 'label');

                return $values['الطلبات'] === 1
                    && $values['المراكب المسجّلة'] === 1
                    && $values['البحارة'] === 4
                    && $values['أسواق السمك'] === 1
                    && $values['محلات ودكات'] === 1
                    && $values['عمالة الأسواق'] === 1
                    && $values['الدلالين'] === 1;
            });
    }

    public function test_governorate_filter_narrows_submissions_and_markets(): void
    {
        $region = Region::factory()->create();
        $selected = Governorate::factory()->for($region)->create();
        $other = Governorate::factory()->for($region)->create();
        $selectedPort = Port::factory()->for($selected)->create();
        $otherPort = Port::factory()->for($other)->create();
        InformationSubmission::factory()->create(['port_id' => $selectedPort->id]);
        InformationSubmission::factory()->create(['port_id' => $otherPort->id]);
        FishMarket::factory()->for($selected)->create();
        FishMarket::factory()->for($other)->create();

        $this->actingAs($this->reviewer())
            ->get(route('information.admin.dashboard', [
                'region_id' => $region->id,
                'governorate_id' => $selected->id,
            ]))
            ->assertOk()
            ->assertViewHas('hero', fn (array $hero): bool => $hero['value'] === 1)
            ->assertViewHas('census', fn (array $census): bool => collect($census)->firstWhere('label', 'أسواق السمك')['value'] === 1);
    }

    public function test_market_and_broker_writes_invalidate_cached_panels(): void
    {
        $market = FishMarket::factory()->create();
        $reviewer = $this->reviewer();

        $this->actingAs($reviewer)
            ->get(route('information.admin.dashboard'))
            ->assertViewHas('census', fn (array $census): bool => collect($census)->firstWhere('label', 'الدلالين')['value'] === 0);

        FishMarketBroker::factory()->for($market, 'market')->create();

        $this->get(route('information.admin.dashboard'))
            ->assertOk()
            ->assertViewHas('census', fn (array $census): bool => collect($census)->firstWhere('label', 'الدلالين')['value'] === 1);
    }

    public function test_dashboard_query_count_is_constant_with_data_volume(): void
    {
        $port = Port::factory()->create();
        InformationSubmission::factory()->count(5)->create(['port_id' => $port->id]);
        $reviewer = $this->reviewer();
        $activeCounter = null;
        $smallDataQueryCount = 0;
        $largeDataQueryCount = 0;
        DB::listen(function () use (&$activeCounter, &$smallDataQueryCount, &$largeDataQueryCount): void {
            if ($activeCounter === 'small') {
                $smallDataQueryCount++;
            } elseif ($activeCounter === 'large') {
                $largeDataQueryCount++;
            }
        });

        $activeCounter = 'small';
        $this->actingAs($reviewer)
            ->get(route('information.admin.dashboard'))
            ->assertOk();
        $activeCounter = null;

        InformationSubmission::factory()->count(50)->create(['port_id' => $port->id]);

        $activeCounter = 'large';
        $this->get(route('information.admin.dashboard'))->assertOk();
        $activeCounter = null;

        $this->assertSame($smallDataQueryCount, $largeDataQueryCount);
    }

    private function recordEvent(InformationSubmission $submission, string $toStatus, Carbon $createdAt, User $actor): InformationSubmissionEvent
    {
        $event = $submission->events()->create([
            'event_type' => 'status_changed',
            'from_status' => $submission->status,
            'to_status' => $toStatus,
            'actor_user_id' => $actor->id,
        ]);
        $event->forceFill(['created_at' => $createdAt])->save();

        return $event;
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
