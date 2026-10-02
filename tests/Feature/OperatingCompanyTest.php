<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Boat;
use App\Models\CounterApplication;
use App\Models\HiringRound;
use App\Models\OperatingCompany;
use App\Models\Port;
use App\Models\Role;
use App\Models\StatisticsOfficer;
use App\Models\Trip;
use App\Models\User;
use App\Services\Sms\ArraySmsSender;
use App\Services\Sms\SmsSender;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * شركات التشغيل والعدّادون الذين توظّفهم: المدير العام ينشئ الشركة ويسند
 * موانئها، والشركة تفتح جولة توظيف، والمتقدّم يقدّم ويوثّق جواله، والشركة
 * تعتمده فيعمل عدّادًا في طابور الميناء، ثم توقفه أو تنقله — وإيقاف
 * الوزارة لا ترفعه الشركة.
 */
class OperatingCompanyTest extends TestCase
{
    use RefreshDatabase;

    private ArraySmsSender $sms;

    private User $admin;

    private OperatingCompany $company;

    private User $staff;

    private Port $port;

    private Port $secondPort;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        $this->sms = new ArraySmsSender;
        $this->app->instance(SmsSender::class, $this->sms);

        $this->admin = User::factory()->superAdmin()->create();
        $this->port = Port::factory()->create(['name' => 'ميناء القطيف (اختبار)']);
        $this->secondPort = Port::factory()->create(['name' => 'ميناء دارين (اختبار)']);
        $this->company = OperatingCompany::factory()->operating($this->port, $this->secondPort)->create(['name' => 'شركة الساحل (اختبار)']);
        $this->staff = User::factory()->company($this->company)->create(['name' => 'موظف الشركة']);
    }

    private function round(array $attributes = []): HiringRound
    {
        return HiringRound::factory()->atPortOf($this->company, $this->port)->create($attributes);
    }

    private function payload(HiringRound $round, array $overrides = []): array
    {
        return $overrides + [
            'hiring_round_id' => $round->id,
            'name' => 'سعد القحطاني',
            'phone' => '+966 55 765 4321',
            'national_id' => '1098765432',
            'birth_date' => '1995-04-01',
            'qualification' => 'ثانوية عامة',
            'experience_years' => 2,
            'password' => 'secret-123',
            'password_confirmation' => 'secret-123',
        ];
    }

    private function codeSentTo(string $phone): string
    {
        preg_match('/\d{6}/', (string) $this->sms->lastTo($phone), $m);

        return $m[0];
    }

    /**
     * طلب قُدّم ووُثّق جواله — نقطة بدء اختبارات المراجعة.
     */
    private function verifiedApplication(?HiringRound $round = null, array $attributes = []): CounterApplication
    {
        return CounterApplication::factory()->inRound($round ?? $this->round())->create($attributes);
    }

    /* ---------------------------------------------------------------
     * المدير العام: الشركات وموانئها وحساباتها
     * ------------------------------------------------------------- */

    public function test_super_admin_creates_a_company_and_assigns_it_a_free_port(): void
    {
        $port = Port::factory()->create(['name' => 'ميناء جازان (اختبار)']);

        $this->actingAs($this->admin)->post(route('panel.companies.store'), [
            'name' => 'شركة الخليج للتشغيل',
            'commercial_register' => '1010999999',
            'phone' => '0551112222',
            'status' => OperatingCompany::ACTIVE,
        ])->assertRedirect();

        $company = OperatingCompany::where('name', 'شركة الخليج للتشغيل')->sole();

        $this->actingAs($this->admin)->post(route('panel.companies.ports.attach', $company), ['port_id' => $port->id])
            ->assertSessionHasNoErrors();
        $this->assertTrue($company->operatesPort($port->id));

        // ميناء تشغّله شركة أخرى لا يُسند مرتين.
        $this->actingAs($this->admin)->post(route('panel.companies.ports.attach', $company), ['port_id' => $this->port->id])
            ->assertSessionHasErrors('port_id');

        $this->actingAs($this->admin)->get(route('panel.companies.show', $company))
            ->assertOk()
            ->assertSee('ميناء جازان (اختبار)');
    }

    public function test_super_admin_creates_a_company_staff_account_and_the_users_page_requires_a_company(): void
    {
        $this->actingAs($this->admin)->post(route('panel.companies.staff.store', $this->company), [
            'name' => 'مشرف التوظيف',
            'phone' => '0553334444',
            'password' => 'secret-123',
        ])->assertSessionHasNoErrors();

        $user = User::where('phone', '0553334444')->sole();
        $this->assertTrue($user->hasAppRole(Role::COMPANY));
        $this->assertSame($this->company->id, $user->operating_company_id);

        $this->actingAs($this->admin)->post(route('panel.users.store'), [
            'name' => 'بلا شركة',
            'phone' => '0553335555',
            'role_id' => Role::key(Role::COMPANY)->id,
            'password' => 'secret-123',
        ])->assertSessionHasErrors('operating_company_id');
    }

    public function test_a_company_with_counters_cannot_be_deleted_and_a_port_with_counters_cannot_be_detached(): void
    {
        $officer = StatisticsOfficer::factory()->atPort($this->port)->create(['operating_company_id' => $this->company->id]);

        $this->actingAs($this->admin)->delete(route('panel.companies.destroy', $this->company))
            ->assertSessionHasErrors('company');
        $this->assertModelExists($this->company);

        $this->actingAs($this->admin)->delete(route('panel.companies.ports.detach', [$this->company, $this->port]))
            ->assertSessionHasErrors('port');

        $officer->delete();
        $this->actingAs($this->admin)->delete(route('panel.companies.ports.detach', [$this->company, $this->secondPort]))
            ->assertSessionHasNoErrors();
        $this->assertFalse($this->company->operatesPort($this->secondPort->id));
    }

    /* ---------------------------------------------------------------
     * بوابة الشركة
     * ------------------------------------------------------------- */

    public function test_company_home_shows_its_dashboard_and_sidebar(): void
    {
        $this->verifiedApplication();

        $this->actingAs($this->staff)->get(route('panel.home'))
            ->assertOk()
            ->assertSee('شركة الساحل (اختبار)')
            ->assertSee('جولات التوظيف')
            ->assertSee('طلبات بانتظارك');
    }

    public function test_a_suspended_company_cannot_use_its_portal(): void
    {
        $this->company->update(['status' => OperatingCompany::SUSPENDED]);

        $this->actingAs($this->staff)->get(route('panel.home'))->assertForbidden();
        $this->actingAs($this->staff)->get(route('panel.company.applications'))->assertForbidden();
    }

    public function test_other_roles_cannot_open_the_company_portal(): void
    {
        $this->actingAs(User::factory()->owner()->create())->get(route('panel.company.hiring'))->assertForbidden();
    }

    public function test_company_opens_a_round_only_at_its_own_ports(): void
    {
        $foreign = Port::factory()->create();

        $this->actingAs($this->staff)->post(route('panel.company.hiring.store'), [
            'port_id' => $foreign->id,
            'title' => 'جولة في ميناء ليس لنا',
            'seats' => 2,
            'opens_at' => today()->toDateString(),
            'closes_at' => today()->addWeek()->toDateString(),
            'status' => HiringRound::OPEN,
        ])->assertSessionHasErrors('port_id');

        $this->actingAs($this->staff)->post(route('panel.company.hiring.store'), [
            'port_id' => $this->port->id,
            'title' => 'توظيف موسم الشتاء',
            'seats' => 2,
            'opens_at' => today()->toDateString(),
            'closes_at' => today()->addWeek()->toDateString(),
            'status' => HiringRound::OPEN,
        ])->assertSessionHasNoErrors();

        $round = HiringRound::where('title', 'توظيف موسم الشتاء')->sole();
        $this->assertTrue($round->isAccepting());
        $this->assertSame($this->staff->id, $round->created_by);
    }

    public function test_seats_cannot_drop_below_the_approved_count(): void
    {
        $round = $this->round(['seats' => 3]);
        CounterApplication::factory()->inRound($round)->approved()->count(2)->create();

        $this->actingAs($this->staff)->put(route('panel.company.hiring.update', $round->id), [
            'port_id' => $this->port->id,
            'title' => $round->title,
            'seats' => 1,
            'opens_at' => $round->opens_at->toDateString(),
            'closes_at' => $round->closes_at->toDateString(),
            'status' => HiringRound::OPEN,
        ])->assertSessionHasErrors('seats');
    }

    public function test_a_round_of_another_company_is_not_found(): void
    {
        $other = OperatingCompany::factory()->operating(Port::factory()->create())->create();
        $round = HiringRound::factory()->atPortOf($other, $other->ports->first())->create();

        $this->actingAs($this->staff)->post(route('panel.company.hiring.close', $round->id))->assertNotFound();
    }

    /* ---------------------------------------------------------------
     * التقديم العام والتوثيق
     * ------------------------------------------------------------- */

    public function test_the_apply_page_lists_only_rounds_that_accept_applications(): void
    {
        $this->round(['title' => 'جولة مفتوحة']);
        HiringRound::factory()->atPortOf($this->company, $this->secondPort)->draft()->create(['title' => 'جولة مسودة']);
        HiringRound::factory()->atPortOf($this->company, $this->secondPort)->create(['title' => 'جولة منتهية', 'opens_at' => today()->subMonth(), 'closes_at' => today()->subDay()]);

        $this->get(route('counter-apply'))
            ->assertOk()
            ->assertSee('جولة مفتوحة')
            ->assertDontSee('جولة مسودة')
            ->assertDontSee('جولة منتهية');
    }

    public function test_an_applicant_applies_verifies_the_phone_and_reaches_the_company(): void
    {
        $round = $this->round();

        $response = $this->post(route('counter-apply.store'), $this->payload($round));

        $application = CounterApplication::sole();
        $response->assertRedirect(route('counter-apply.show', $application->token));
        $this->assertSame('0557654321', $application->phone);
        $this->assertSame($this->company->id, $application->operating_company_id);
        $this->assertSame($this->port->id, $application->port_id);
        $this->assertFalse($application->isVerified());

        // قبل التوثيق لا يراه موظف الشركة.
        $this->actingAs($this->staff)->get(route('panel.company.applications'))->assertDontSee('سعد القحطاني');

        $this->post(route('counter-apply.verify', $application->token), ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post(route('counter-apply.verify', $application->token), ['code' => $this->codeSentTo('0557654321')])
            ->assertSessionHasNoErrors();

        $this->assertTrue($application->fresh()->isVerified());
        $this->assertSame(1, AppNotification::forUser($this->staff)->count());

        $this->actingAs($this->staff)->get(route('panel.company.applications'))
            ->assertOk()
            ->assertSee('سعد القحطاني')
            ->assertSee('1098765432');
    }

    public function test_applying_to_a_full_round_or_with_a_registered_phone_is_refused(): void
    {
        $round = $this->round(['seats' => 1]);
        CounterApplication::factory()->inRound($round)->approved()->create();

        $this->post(route('counter-apply.store'), $this->payload($round))->assertSessionHasErrors('hiring_round_id');

        User::factory()->owner()->create(['phone' => '0557654321']);
        $this->post(route('counter-apply.store'), $this->payload($this->round()))->assertSessionHasErrors('phone');

        $this->assertSame(1, CounterApplication::count());
    }

    public function test_reapplying_before_verification_replaces_the_unverified_application(): void
    {
        $round = $this->round();

        $this->post(route('counter-apply.store'), $this->payload($round));
        $this->post(route('counter-apply.store'), $this->payload($round, ['name' => 'سعد بن علي']));

        $this->assertSame('سعد بن علي', CounterApplication::sole()->name);
    }

    public function test_an_applicant_withdraws_a_pending_application(): void
    {
        $application = $this->verifiedApplication();

        $this->post(route('counter-apply.withdraw', $application->token))->assertSessionHasNoErrors();

        $this->assertSame(CounterApplication::WITHDRAWN, $application->fresh()->status);
    }

    /* ---------------------------------------------------------------
     * المراجعة والعمل
     * ------------------------------------------------------------- */

    public function test_approval_creates_a_counter_who_works_the_port_queue(): void
    {
        $application = $this->verifiedApplication(null, ['name' => 'عدّاد معتمد', 'phone' => '0551230000', 'password' => 'secret-123']);

        $this->actingAs($this->staff)->post(route('panel.company.applications.approve', $application->id))
            ->assertSessionHasNoErrors();

        $user = User::where('phone', '0551230000')->sole();
        $this->assertTrue($user->hasAppRole(Role::COUNTER));
        $this->assertTrue(password_verify('secret-123', $user->password));

        $officer = $user->statisticsOfficer;
        $this->assertSame($this->port->id, $officer->port_id);
        $this->assertSame($this->company->id, $officer->operating_company_id);
        $this->assertSame($application->id, $officer->counter_application_id);
        $this->assertSame(CounterApplication::APPROVED, $application->fresh()->status);
        $this->assertStringContainsString('قُبل طلبك', (string) $this->sms->lastTo('0551230000'));

        // يعمل في طابور الميناء كعدّاد الوزارة تمامًا.
        $owner = User::factory()->owner()->create();
        $boat = Boat::factory()->ownedBy($owner)->create(['port_id' => $this->port->id]);
        $trip = Trip::factory()->onBoat($boat)->create(['return_port_id' => $this->port->id, 'status' => Trip::AWAITING_COUNT]);

        $this->assertTrue(Trip::forCounter($user)->whereKey($trip->id)->exists());
        $this->actingAs($user)->get(route('panel.counter.trips'))->assertOk();
    }

    public function test_approval_respects_the_seats_left(): void
    {
        $round = $this->round(['seats' => 1]);
        CounterApplication::factory()->inRound($round)->approved()->create();
        $application = $this->verifiedApplication($round);

        $this->actingAs($this->staff)->post(route('panel.company.applications.approve', $application->id))
            ->assertSessionHasErrors('review');

        $this->assertTrue($application->fresh()->isPending());
    }

    public function test_rejection_saves_the_reason_and_texts_the_applicant(): void
    {
        $application = $this->verifiedApplication(null, ['phone' => '0551239999']);

        $this->actingAs($this->staff)->post(route('panel.company.applications.reject', $application->id), ['rejection_reason' => 'اكتمل العدد'])
            ->assertSessionHasNoErrors();

        $this->assertSame(CounterApplication::REJECTED, $application->fresh()->status);
        $this->assertStringContainsString('اكتمل العدد', (string) $this->sms->lastTo('0551239999'));
        $this->assertNull(User::where('phone', '0551239999')->first());
    }

    public function test_a_company_cannot_review_another_companys_or_unverified_applications(): void
    {
        $other = OperatingCompany::factory()->operating(Port::factory()->create())->create();
        $foreign = CounterApplication::factory()->inRound(HiringRound::factory()->atPortOf($other, $other->ports->first())->create())->create();
        $unverified = CounterApplication::factory()->inRound($this->round())->unverified()->create();

        $this->actingAs($this->staff)->post(route('panel.company.applications.approve', $foreign->id))->assertNotFound();
        $this->actingAs($this->staff)->post(route('panel.company.applications.approve', $unverified->id))->assertNotFound();
    }

    /* ---------------------------------------------------------------
     * الإيقاف والنقل
     * ------------------------------------------------------------- */

    private function companyCounter(): StatisticsOfficer
    {
        $user = User::factory()->role(Role::COUNTER)->create();

        return StatisticsOfficer::factory()->forUser($user)->atPort($this->port)->create(['operating_company_id' => $this->company->id]);
    }

    public function test_company_suspends_and_reactivates_its_counter(): void
    {
        $officer = $this->companyCounter();

        $this->actingAs($this->staff)->post(route('panel.company.counters.suspend', $officer->id), ['reason' => 'غياب متكرر'])
            ->assertSessionHasNoErrors();

        $this->assertTrue($officer->fresh()->isSuspended());
        $this->assertFalse($officer->user->fresh()->active);

        // الحساب الموقوف يُخرَج من البوابة.
        $this->actingAs($officer->user->fresh())->get(route('panel.home'))->assertRedirect(route('panel.login'));

        $this->actingAs($this->staff)->post(route('panel.company.counters.reactivate', $officer->id))->assertSessionHasNoErrors();
        $this->assertFalse($officer->fresh()->isSuspended());
        $this->assertTrue($officer->user->fresh()->active);
    }

    public function test_a_ministry_suspension_is_lifted_only_by_the_super_admin(): void
    {
        $officer = $this->companyCounter();

        $this->actingAs($this->admin)->post(route('panel.counters.suspend', $officer), ['reason' => 'مخالفة'])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->staff)->post(route('panel.company.counters.reactivate', $officer->id))
            ->assertSessionHasErrors('counter');
        $this->assertTrue($officer->fresh()->isSuspended());

        $this->actingAs($this->admin)->post(route('panel.counters.reactivate', $officer))->assertSessionHasNoErrors();
        $this->assertFalse($officer->fresh()->isSuspended());
    }

    public function test_company_transfers_a_counter_only_between_its_ports(): void
    {
        $officer = $this->companyCounter();
        $foreign = Port::factory()->create();

        $this->actingAs($this->staff)->post(route('panel.company.counters.transfer', $officer->id), ['to_port_id' => $foreign->id])
            ->assertNotFound();

        $this->actingAs($this->staff)->post(route('panel.company.counters.transfer', $officer->id), ['to_port_id' => $this->secondPort->id, 'reason' => 'ضغط في دارين'])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->secondPort->id, $officer->fresh()->port_id);
        $this->assertSame(1, $officer->transfers()->count());
        $this->assertSame(1, AppNotification::forUser($officer->user)->count());
    }

    public function test_a_counter_with_a_trip_under_count_is_not_transferred(): void
    {
        $officer = $this->companyCounter();
        $owner = User::factory()->owner()->create();
        $boat = Boat::factory()->ownedBy($owner)->create(['port_id' => $this->port->id]);
        Trip::factory()->onBoat($boat)->create(['return_port_id' => $this->port->id, 'status' => Trip::COUNTING, 'counter_id' => $officer->user_id]);

        $this->actingAs($this->staff)->post(route('panel.company.counters.transfer', $officer->id), ['to_port_id' => $this->secondPort->id])
            ->assertSessionHasErrors('to_port_id');

        $this->assertSame($this->port->id, $officer->fresh()->port_id);
    }

    public function test_super_admin_sees_ministry_and_company_counters_together(): void
    {
        $this->companyCounter()->update(['name' => 'عدّاد الشركة']);
        $ministry = User::factory()->role(Role::COUNTER)->create();
        StatisticsOfficer::factory()->forUser($ministry)->atPort($this->port)->create(['name' => 'عدّاد الوزارة']);

        $this->actingAs($this->admin)->get(route('panel.counters'))
            ->assertOk()
            ->assertSee('عدّاد الشركة')
            ->assertSee('عدّاد الوزارة')
            ->assertSee('شركة الساحل (اختبار)');

        $this->actingAs($this->admin)->get(route('panel.counters', ['company' => 'ministry']))
            ->assertOk()
            ->assertSee('عدّاد الوزارة')
            ->assertDontSee('عدّاد الشركة');
    }
}
