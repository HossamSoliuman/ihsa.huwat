<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\AuditLog;
use App\Models\Boat;
use App\Models\BoatMaintenance;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Fisher;
use App\Models\MonthClosing;
use App\Models\Payroll;
use App\Models\PayType;
use App\Models\Port;
use App\Models\Sale;
use App\Models\Trip;
use App\Models\User;
use App\Services\Owner\ExpenseService;
use App\Services\Owner\MonthClosingService;
use App\Services\Owner\PayrollService;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O4 — إغلاق الشهر: المعاينة وأرقام القوارب وما لا قارب له، الإغلاق وإنشاء
 * المسيرات الناقصة، انتقال مؤجَّل الإهلاك، قفل المصروفات والمسيرات مع بقاء
 * السداد، التسلسل وإعادة الفتح، والتحذيرات.
 */
class OwnerMonthClosingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Boat $boat;

    private Trip $trip;

    private CarbonImmutable $month;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        $this->owner = User::factory()->owner()->create(['name' => 'مالك الإغلاق']);
        $port = Port::factory()->create(['name' => 'ميناء الإغلاق (اختبار)']);
        $this->boat = Boat::factory()->ownedBy($this->owner)->create(['port_id' => $port->id, 'name' => 'قارب الإغلاق', 'owner_share_percent' => 50]);
        $this->trip = Trip::factory()->forOwner($this->owner)->onBoat($this->boat)->create();
        $this->month = CarbonImmutable::now()->startOfMonth()->subMonth();
    }

    private function asOwner(): static
    {
        return $this->actingAs($this->owner);
    }

    private function member(string $type, array $attributes = [], ?Boat $boat = null): Fisher
    {
        return Fisher::factory()->ownedBy($this->owner)->onBoat($boat ?? $this->boat)->create($attributes + [
            'pay_type_id' => PayType::named($type)->id,
        ]);
    }

    private function directSale(float $ownerNet, ?CarbonImmutable $at = null, ?Trip $trip = null): Sale
    {
        return Sale::factory()->create([
            'seller_id' => $this->owner->id,
            'trip_id' => ($trip ?? $this->trip)->id,
            'subtotal' => $ownerNet,
            'total' => $ownerNet,
            'owner_net' => $ownerNet,
            'sold_at' => ($at ?? $this->month->addDays(9))->setTime(10, 0),
        ]);
    }

    private function expense(float $amount, ?int $boatId = -1, ?CarbonImmutable $on = null): Expense
    {
        return Expense::factory()->ownedBy($this->owner)->create([
            'boat_id' => $boatId === -1 ? $this->boat->id : $boatId,
            'date' => ($on ?? $this->month->addDays(4))->toDateString(),
            'subtotal' => $amount,
            'total' => $amount,
            'paid_amount' => 0,
        ]);
    }

    /**
     * أصل يُهلَك بقسط شهري ثابت منذ سنة.
     */
    private function asset(float $monthly, ?int $boatId = -1): Asset
    {
        return Asset::factory()->ownedBy($this->owner)->create([
            'asset_type_id' => AssetType::named('محرك')->id,
            'boat_id' => $boatId === -1 ? $this->boat->id : $boatId,
            'purchase_date' => $this->month->subYear()->toDateString(),
            'purchase_cost' => $monthly * 60,
            'salvage_value' => 0,
            'useful_life_years' => 5,
        ]);
    }

    private function close(?CarbonImmutable $month = null): MonthClosing
    {
        $month ??= $this->month;

        return app(MonthClosingService::class)->close($this->owner, $month->year, $month->month);
    }

    private function preview(?CarbonImmutable $month = null): array
    {
        $month ??= $this->month;

        return app(MonthClosingService::class)->preview($this->owner, $month->year, $month->month);
    }

    public function test_pages_render_for_the_owner_only_and_sit_in_the_sidebar(): void
    {
        $this->member(PayType::FIXED, ['fixed_salary' => 1000]);
        $this->directSale(5000);
        $period = $this->month->format('Y-m');

        $this->asOwner()->get('/admin/owner/month-closings')->assertOk()->assertSee($period, false);
        $this->asOwner()->get("/admin/owner/month-closings/preview?period={$period}")
            ->assertOk()->assertSee($this->boat->name)->assertSee('يُنشأ مسيره عند الإغلاق');

        $response = $this->asOwner()->post('/admin/owner/month-closings', ['period' => $period, 'notes' => 'مراجعة الشهر']);
        $closing = MonthClosing::sole();
        $response->assertRedirect(route('panel.owner.month-closings.show', $closing->id));

        foreach (['month-closings', "month-closings/{$closing->id}", "month-closings/{$closing->id}/print"] as $page) {
            $this->asOwner()->get("/admin/owner/{$page}")->assertOk()->assertSee($closing->period_label);
        }
        $this->asOwner()->get("/admin/owner/month-closings/{$closing->id}")->assertSee($this->boat->name)->assertSee('مراجعة الشهر')->assertSee(Payroll::sole()->payroll_number);

        $this->asOwner()->get('/admin')->assertSee(route('panel.owner.month-closings'), false);
        $this->actingAs(User::factory()->dalal()->create())->get('/admin/owner/month-closings')->assertForbidden();
    }

    public function test_the_preview_computes_each_boat_and_the_owners_result_without_saving(): void
    {
        // القارب الأول: بيع 10000، مصروف 1500، قسط 200، بحّار بالنسبة وطبّاخ براتب 1000 بلا مسير بعد.
        $this->directSale(10000);
        $this->expense(1500);
        $this->asset(200);
        $sailor = $this->member(PayType::SHARE);
        $this->member(PayType::FIXED, ['fixed_salary' => 1000]);

        // الثاني: بيع 3000 ونسبة المالك 100% بلا أفراد. والثالث بلا نشاط فلا يظهر.
        $second = Boat::factory()->ownedBy($this->owner)->create(['name' => 'قارب ثانٍ', 'owner_share_percent' => 100]);
        $this->directSale(3000, null, Trip::factory()->forOwner($this->owner)->onBoat($second)->create());
        Boat::factory()->ownedBy($this->owner)->create(['name' => 'قارب راكد']);

        // ما لا قارب له: مصروف عام 700 وأصل عام بقسط 100.
        $this->expense(700, null);
        $this->asset(100, null);

        $data = $this->preview();
        $boats = collect($data['boats'])->keyBy('boat_name');

        $this->assertSame(['قارب الإغلاق', 'قارب ثانٍ'], $boats->keys()->sort()->values()->all());

        $first = $boats['قارب الإغلاق'];
        $this->assertEqualsWithDelta(10000, $first['revenue'], 0.001);
        $this->assertEqualsWithDelta(2500, $first['expenses'], 0.001);          // 1500 + الراتب الثابت الذي سيُرحَّل
        $this->assertEqualsWithDelta(1000, $first['pending_fixed'], 0.001);
        $this->assertEqualsWithDelta(200, $first['depreciation_charged'], 0.001);
        $this->assertEqualsWithDelta(7300, $first['net_profit'], 0.001);
        $this->assertEqualsWithDelta(3650, $first['crew_pool'], 0.001);
        $this->assertEqualsWithDelta(3650, collect($first['dues'])->firstWhere('name', $sailor->name)['gross'], 0.001);

        $this->assertEqualsWithDelta(3000, $boats['قارب ثانٍ']['owner_share'], 0.001);
        $this->assertEqualsWithDelta(0, $boats['قارب ثانٍ']['crew_pool'], 0.001);

        $this->assertEqualsWithDelta(700, $data['general']['expenses'], 0.001);
        $this->assertEqualsWithDelta(100, $data['general']['depreciation'], 0.001);
        // نصيب المالك 3650 + 3000 − 700 − 100.
        $this->assertEqualsWithDelta(5850, $data['totals']['owner_net'], 0.001);

        $this->assertSame(0, MonthClosing::count());
        $this->assertSame(0, Payroll::count());
    }

    public function test_closing_freezes_the_snapshot_and_creates_the_missing_payrolls(): void
    {
        $sailor = $this->member(PayType::SHARE);
        $this->member(PayType::FIXED, ['fixed_salary' => 1000]);
        $this->directSale(10000);

        $closing = $this->close();

        $payroll = Payroll::sole();
        $row = $closing->boats()->sole();
        $this->assertSame($payroll->id, $row->payroll_id);
        $this->assertEqualsWithDelta(9000, $row->net_profit, 0.001);             // الراتب الثابت رُحِّل مصروفًا
        $this->assertEqualsWithDelta(4500, $closing->crew_pool, 0.001);
        $this->assertEqualsWithDelta(4500, $closing->owner_net, 0.001);
        $this->assertEqualsWithDelta(4500, $payroll->lines()->where('fisher_id', $sailor->id)->value('base_amount'), 0.001);
        $this->assertTrue(AuditLog::where('entity', 'MonthClosing')->where('action', 'إغلاق شهر')->exists());

        // بيع يصل متأخرًا بتاريخ الشهر لا يغيّر اللقطة ولا المسير المجمَّد.
        $this->directSale(6000, $this->month->addDays(20));
        $this->asOwner()->get("/admin/owner/payrolls/{$payroll->id}")->assertOk()->assertSee('الشهر مُغلق');
        $this->assertEqualsWithDelta(4500, $payroll->lines()->where('fisher_id', $sailor->id)->value('base_amount'), 0.001);
        $this->asOwner()->get("/admin/owner/month-closings/{$closing->id}")->assertOk()->assertSee('4,500.00');
        $this->assertEqualsWithDelta(10000, $closing->fresh()->revenue, 0.001);
    }

    public function test_deferred_depreciation_carries_into_the_next_month(): void
    {
        $first = $this->month->subMonth();
        $sailor = $this->member(PayType::SHARE);
        $this->asset(200);
        $this->directSale(100, $first->addDays(3));
        $this->directSale(1000);

        // قبل إغلاق الشهر الأول: مسير الثاني لا يعرف مؤجَّلًا.
        $payroll = app(PayrollService::class)->generate($this->owner, $this->boat, $this->month->year, $this->month->month);
        $this->assertEqualsWithDelta(200, $payroll->depreciation, 0.001);

        $closing = $this->close($first);
        $this->assertEqualsWithDelta(100, $closing->depreciation_charged, 0.001);
        $this->assertEqualsWithDelta(100, $closing->depreciation_deferred, 0.001);
        $this->assertEqualsWithDelta(0, $closing->crew_pool, 0.001);

        // المسير المفتوح يلتقط المؤجَّل عند فتحه: 200 + 100 من ربح 1000.
        $this->asOwner()->get("/admin/owner/payrolls/{$payroll->id}")->assertOk();
        $payroll->refresh();
        $this->assertEqualsWithDelta(300, $payroll->depreciation_charged, 0.001);
        $this->assertEqualsWithDelta(350, $payroll->crew_pool, 0.001);
        $this->assertEqualsWithDelta(350, $payroll->lines()->where('fisher_id', $sailor->id)->value('base_amount'), 0.001);

        $row = collect($this->preview()['boats'])->sole();
        $this->assertEqualsWithDelta(200, $row['depreciation_own'], 0.001);
        $this->assertEqualsWithDelta(100, $row['depreciation_brought_forward'], 0.001);

        $this->assertEqualsWithDelta(0, $this->close()->depreciation_deferred, 0.001);
    }

    public function test_a_closed_month_locks_its_expenses_but_not_their_payment(): void
    {
        $expense = $this->expense(500);
        $category = ExpenseCategory::query()->value('id');
        $this->close();

        $payload = ['expense_category_id' => $category, 'date' => $this->month->addDays(6)->toDateString(), 'subtotal' => 300];

        $this->asOwner()->post('/admin/owner/expenses', $payload)->assertSessionHasErrors('date');
        $this->asOwner()->put("/admin/owner/expenses/{$expense->id}", ['subtotal' => 900, 'date' => $expense->date->toDateString(), 'expense_category_id' => $expense->expense_category_id])->assertSessionHasErrors('date');
        // ولا يُنقل سند شهر مفتوح إلى الشهر المُغلق.
        $open = $this->expense(200, -1, CarbonImmutable::now());
        $this->asOwner()->put("/admin/owner/expenses/{$open->id}", ['subtotal' => 200, 'date' => $this->month->addDay()->toDateString(), 'expense_category_id' => $open->expense_category_id])->assertSessionHasErrors('date');
        $this->asOwner()->delete("/admin/owner/expenses/{$expense->id}")->assertSessionHasErrors('expense');

        $this->assertEqualsWithDelta(500, $expense->fresh()->total, 0.001);
        $this->assertSame(2, Expense::count());

        $this->asOwner()->post("/admin/owner/expenses/{$expense->id}/payment", ['amount' => 100])->assertSessionHasNoErrors();
        $this->assertEqualsWithDelta(100, $expense->fresh()->paid_amount, 0.001);

        $this->asOwner()->post('/admin/owner/expenses', ['date' => CarbonImmutable::now()->toDateString()] + $payload)->assertSessionHasNoErrors();
        $this->asOwner()->get('/admin/owner/expenses')->assertOk()->assertSee('مُغلق');
    }

    public function test_a_closed_months_payroll_is_frozen_but_still_payable(): void
    {
        $share = $this->member(PayType::SHARE);
        $this->member(PayType::FIXED, ['fixed_salary' => 1000]);
        $this->directSale(4000);
        $this->close();

        $payroll = Payroll::sole();
        $line = $payroll->lines()->where('fisher_id', $share->id)->first();
        $this->assertEqualsWithDelta(1500, $line->base_amount, 0.001);

        $this->asOwner()->put("/admin/owner/payrolls/{$payroll->id}/lines/{$line->id}", ['bonus' => 200])->assertSessionHasErrors('line');
        $this->asOwner()->delete("/admin/owner/payrolls/{$payroll->id}")->assertSessionHasErrors('payroll');
        $this->assertModelExists($payroll);

        $other = Boat::factory()->ownedBy($this->owner)->create();
        $this->member(PayType::SHARE, [], $other);
        $this->asOwner()->post('/admin/owner/payrolls', ['boat_id' => $other->id, 'period' => $this->month->format('Y-m')])->assertSessionHasErrors('period');

        $this->asOwner()->post("/admin/owner/payrolls/{$payroll->id}/pay-all")->assertSessionHasNoErrors();
        $this->assertSame(ExpenseService::PAID, $payroll->fresh()->paymentStatus->name);
        $this->assertEqualsWithDelta(1500, $line->fresh()->paid_amount, 0.001);
        $this->assertEqualsWithDelta(1000, $payroll->expense()->first()->paid_amount, 0.001);
    }

    public function test_posting_maintenance_into_a_closed_month_is_refused_and_rolled_back(): void
    {
        $this->close();

        $this->asOwner()->post('/admin/owner/maintenance', [
            'boat_id' => $this->boat->id,
            'date' => $this->month->addDays(8)->toDateString(),
            'actual_cost' => 800,
            'status' => BoatMaintenance::COMPLETED,
        ])->assertSessionHasErrors('date');

        $this->assertSame(0, BoatMaintenance::count());
        $this->assertSame(0, Expense::count());

        // صيانة معلقة بلا سند لا تمسّ الشهر فتُقبل.
        $this->asOwner()->post('/admin/owner/maintenance', [
            'boat_id' => $this->boat->id,
            'date' => $this->month->addDays(8)->toDateString(),
            'estimated_cost' => 800,
        ])->assertSessionHasNoErrors();
        $this->assertSame(1, BoatMaintenance::count());
    }

    public function test_months_close_in_sequence_and_only_the_latest_reopens(): void
    {
        $first = $this->month->subMonth();

        $this->asOwner()->post('/admin/owner/month-closings', ['period' => CarbonImmutable::now()->format('Y-m')])->assertSessionHasErrors('period');

        $older = $this->asOwner()->post('/admin/owner/month-closings', ['period' => $first->format('Y-m')]);
        $older->assertSessionHasNoErrors();
        $closingOne = MonthClosing::sole();

        $this->asOwner()->post('/admin/owner/month-closings', ['period' => $first->format('Y-m')])->assertSessionHasErrors('period');
        $this->asOwner()->post('/admin/owner/month-closings', ['period' => $first->subMonth()->format('Y-m')])->assertSessionHasErrors('period');
        $this->asOwner()->get('/admin/owner/month-closings/preview?period='.$first->subMonth()->format('Y-m'))->assertSessionHasErrors('period');

        $this->asOwner()->post('/admin/owner/month-closings', ['period' => $this->month->format('Y-m')])->assertSessionHasNoErrors();
        $closingTwo = MonthClosing::latestFirst()->first();

        $this->asOwner()->delete("/admin/owner/month-closings/{$closingOne->id}")->assertSessionHasErrors('closing');
        $this->assertModelExists($closingOne);

        $this->asOwner()->delete("/admin/owner/month-closings/{$closingTwo->id}")->assertRedirect(route('panel.owner.month-closings'));
        $this->assertModelMissing($closingTwo);
        $this->assertTrue(app(MonthClosingService::class)->nextPeriod($this->owner)->equalTo($this->month));
    }

    public function test_reopening_unfreezes_the_payroll(): void
    {
        $share = $this->member(PayType::SHARE);
        $this->directSale(2000);
        $closing = $this->close();
        $payroll = Payroll::sole();

        $this->directSale(2000, $this->month->addDays(15));
        $this->asOwner()->get("/admin/owner/payrolls/{$payroll->id}")->assertOk();
        $this->assertEqualsWithDelta(1000, $payroll->lines()->where('fisher_id', $share->id)->value('base_amount'), 0.001);

        $this->asOwner()->delete("/admin/owner/month-closings/{$closing->id}")->assertSessionHasNoErrors();
        $this->assertTrue(AuditLog::where('entity', 'MonthClosing')->where('action', 'إعادة فتح شهر')->exists());

        $this->asOwner()->get("/admin/owner/payrolls/{$payroll->id}")->assertOk();
        $this->assertEqualsWithDelta(2000, $payroll->lines()->where('fisher_id', $share->id)->value('base_amount'), 0.001);
    }

    public function test_the_preview_warns_before_closing(): void
    {
        // نصيب طاقم بلا أصحاب نسبة.
        $this->member(PayType::FIXED, ['fixed_salary' => 500]);
        $this->directSale(3000);

        // رحلة من الشهر لم يُبع مصيدها كله، ومصروف غير مسدَّد.
        Trip::factory()->forOwner($this->owner)->onBoat($this->boat)->readyForSale()->create(['departure_time' => $this->month->addDays(2)]);
        $this->expense(250);

        // مسير قارب آخر سُدِّد كاملًا ثم تغيّرت أرقامه.
        $other = Boat::factory()->ownedBy($this->owner)->create(['name' => 'قارب مسدَّد']);
        $otherTrip = Trip::factory()->forOwner($this->owner)->onBoat($other)->create();
        $this->member(PayType::SHARE, [], $other);
        $this->directSale(1000, null, $otherTrip);
        $paid = app(PayrollService::class)->generate($this->owner, $other, $this->month->year, $this->month->month);
        app(PayrollService::class)->payAll($paid, null, $this->owner);
        $this->directSale(1000, null, $otherTrip);

        $warnings = implode("\n", $this->preview()['warnings']);

        $this->assertStringContainsString('بلا مستحق', $warnings);
        $this->assertStringContainsString('لم يُبع مصيدها كله', $warnings);
        $this->assertStringContainsString('غير المسدَّدة', $warnings);
        $this->assertStringContainsString($paid->payroll_number, $warnings);

        $this->asOwner()->get('/admin/owner/month-closings/preview?period='.$this->month->format('Y-m'))->assertOk()->assertSee('قبل الإغلاق');
    }

    public function test_another_owners_closing_is_refused(): void
    {
        $closing = $this->close();
        $stranger = User::factory()->owner()->create();

        $this->actingAs($stranger)->get("/admin/owner/month-closings/{$closing->id}")->assertNotFound();
        $this->actingAs($stranger)->get("/admin/owner/month-closings/{$closing->id}/print")->assertNotFound();
        $this->actingAs($stranger)->delete("/admin/owner/month-closings/{$closing->id}")->assertNotFound();
        $this->assertModelExists($closing);

        // إغلاق مالك لا يقفل شهر غيره.
        $this->actingAs($stranger)->post('/admin/owner/month-closings', ['period' => $this->month->format('Y-m')])->assertSessionHasNoErrors();
        $this->assertSame(2, MonthClosing::count());
    }
}
