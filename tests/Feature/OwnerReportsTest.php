<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Fisher;
use App\Models\MonthClosing;
use App\Models\Payroll;
use App\Models\PayType;
use App\Models\Port;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Species;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Owner\MonthClosingService;
use App\Services\Owner\OwnerReports;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O6 — تقارير المالك: المركز والصفحات والطباعة وExcel، وأن أرقام قائمة
 * الأرباح والملخصين وربحية القوارب تطابق إغلاق الشهر، وربحية الرحلات،
 * والإنتاج حسب الصنف، والمصروفات حسب الفئة، وكشفا العميل والمورد.
 *
 * الشهر الماضي: بيع مباشر 200 كجم بـ10000، ودلال يبيع 100 كجم بـ6000
 * (عمولة 300 + أجور 120 → صافي 5580)، مصروف قارب 1500 + مصروف رحلة 400،
 * مصروف عام 500، قسط أصل 200، نسبة المالك 50%.
 */
class OwnerReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $dalal;

    private Boat $boat;

    private Trip $trip;

    private Species $hamour;

    private CarbonImmutable $month;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        $this->owner = User::factory()->owner()->create(['name' => 'مالك التقارير']);
        $this->dalal = User::factory()->dalal()->create(['name' => 'دلال التقارير']);
        $port = Port::factory()->create(['name' => 'ميناء التقارير (اختبار)']);
        $this->boat = Boat::factory()->ownedBy($this->owner)->create(['port_id' => $port->id, 'name' => 'قارب التقارير', 'owner_share_percent' => 50]);
        $this->month = CarbonImmutable::now()->startOfMonth()->subMonth();
        $this->trip = Trip::factory()->forOwner($this->owner)->onBoat($this->boat)->create([
            'trip_number' => 'TR-RPT-1',
            'departure_time' => $this->month->addDays(2)->setTime(6, 0),
        ]);
        $this->hamour = Species::factory()->create(['name_ar' => 'هامور التقارير']);
    }

    private function asOwner(): static
    {
        return $this->actingAs($this->owner);
    }

    private function seedMonth(): void
    {
        CatchRecord::factory()->create(['trip_id' => $this->trip->id, 'species_id' => $this->hamour->id, 'quantity_kg' => 340, 'captain_kg' => 340, 'counted_kg' => 350]);

        $direct = Sale::factory()->create([
            'seller_id' => $this->owner->id, 'trip_id' => $this->trip->id,
            'subtotal' => 10000, 'total' => 10000, 'owner_net' => 10000, 'paid_amount' => 10000,
            'sold_at' => $this->month->addDays(9)->setTime(10, 0),
        ]);
        SaleItem::factory()->create(['sale_id' => $direct->id, 'species_id' => $this->hamour->id, 'trip_id' => $this->trip->id, 'weight_kg' => 200, 'price_per_kg' => 50, 'total' => 10000, 'owner_net' => 10000]);

        $viaDalal = Sale::factory()->create([
            'seller_id' => $this->dalal->id, 'trip_id' => null,
            'subtotal' => 6000, 'total' => 6000, 'commission_amount' => 300, 'wage_amount' => 120, 'owner_net' => 5580,
            'sold_at' => $this->month->addDays(10)->setTime(10, 0),
        ]);
        SaleItem::factory()->create([
            'sale_id' => $viaDalal->id, 'species_id' => $this->hamour->id, 'trip_id' => $this->trip->id, 'owner_id' => $this->owner->id,
            'weight_kg' => 100, 'price_per_kg' => 60, 'total' => 6000, 'commission_amount' => 300, 'wage_amount' => 120, 'owner_net' => 5580,
        ]);

        $this->expense(1500);
        $this->expense(400, tripId: $this->trip->id);
        $this->expense(500, boatId: null);

        Asset::factory()->ownedBy($this->owner)->create([
            'asset_type_id' => AssetType::named('محرك')->id,
            'boat_id' => $this->boat->id,
            'purchase_date' => $this->month->subYear()->toDateString(),
            'purchase_cost' => 200 * 60,
            'salvage_value' => 0,
            'useful_life_years' => 5,
        ]);
    }

    private function expense(float $amount, ?int $boatId = -1, ?int $tripId = null, ?Vendor $vendor = null, ?CarbonImmutable $on = null, float $paid = 0): Expense
    {
        return Expense::factory()->ownedBy($this->owner)->create([
            'boat_id' => $boatId === -1 ? $this->boat->id : $boatId,
            'trip_id' => $tripId,
            'vendor_id' => $vendor?->id,
            'date' => ($on ?? $this->month->addDays(4))->toDateString(),
            'subtotal' => $amount,
            'discount' => 0,
            'vat_amount' => 0,
            'total' => $amount,
            'paid_amount' => $paid,
        ]);
    }

    private function reports(): OwnerReports
    {
        return app(OwnerReports::class);
    }

    public function test_hub_pages_prints_and_excel_render_for_the_owner_only(): void
    {
        $this->seedMonth();
        $customer = Customer::factory()->ofAccount($this->owner)->create(['name' => 'عميل التقارير']);
        $vendor = Vendor::factory()->create(['owner_id' => $this->owner->id, 'name' => 'مورد التقارير']);
        $period = $this->month->format('Y-m');

        $this->asOwner()->get('/admin/owner/reports')->assertOk()
            ->assertSee('قائمة الأرباح والخسائر')->assertSee('عميل التقارير')->assertSee('مورد التقارير');
        $this->asOwner()->get('/admin')->assertSee(route('panel.owner.reports'), false);

        $pages = [
            'profit-loss' => "from={$period}&to={$period}",
            'month-summary' => "period={$period}",
            'annual-summary' => 'year='.$this->month->year,
            'expenses-by-category' => 'from='.$this->month->toDateString().'&to='.$this->month->endOfMonth()->toDateString(),
            'boat-profitability' => "from={$period}&to={$period}",
            'trip-profitability' => 'from='.$this->month->toDateString().'&to='.$this->month->endOfMonth()->toDateString(),
            'production' => 'from='.$this->month->toDateString().'&to='.$this->month->endOfMonth()->toDateString(),
            'customer-statement' => "customer_id={$customer->id}",
            'vendor-statement' => "vendor_id={$vendor->id}",
        ];

        foreach ($pages as $report => $query) {
            $this->asOwner()->get("/admin/owner/reports/{$report}?{$query}")->assertOk()->assertSee(OwnerReports::REPORTS[$report]['title']);
            $this->asOwner()->get("/admin/owner/reports/{$report}/print?{$query}")->assertOk()->assertSee($this->owner->name)->assertSee('صفحة');

            $csv = $this->asOwner()->get("/admin/owner/reports/{$report}/export?{$query}");
            $csv->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $this->assertStringStartsWith("\xEF\xBB\xBF", $csv->streamedContent(), $report);
        }

        $this->asOwner()->get("/admin/owner/reports/boat-profitability?from={$period}&to={$period}")->assertSee('قارب التقارير');
        $this->asOwner()->get('/admin/owner/reports/trip-profitability?from='.$this->month->toDateString())->assertSee('TR-RPT-1');
        $this->asOwner()->get('/admin/owner/reports/production?from='.$this->month->toDateString())->assertSee('هامور التقارير');

        $this->asOwner()->get('/admin/owner/reports/unknown')->assertNotFound();
        $this->asOwner()->get('/admin/owner/reports/customer-statement/print')->assertNotFound();
        $this->actingAs($this->dalal)->get('/admin/owner/reports')->assertForbidden();
        $this->actingAs($this->dalal)->get('/admin/owner/reports/profit-loss')->assertForbidden();
    }

    public function test_profit_and_loss_reconciles_with_the_month_closing_and_writes_nothing(): void
    {
        $this->seedMonth();

        $pl = $this->reports()->profitLoss($this->owner, $this->month, $this->month->endOfMonth());
        $f = $pl['figures'];

        $this->assertSame(['direct_net' => 10000.0, 'dalal_gross' => 6000.0, 'dalal_cut' => 420.0, 'dalal_net' => 5580.0, 'net' => 15580.0], $pl['revenue']);
        $this->assertEquals(15580, $f['revenue']);
        $this->assertEquals(2400, $f['total_expenses']);
        $this->assertEquals(200, $f['depreciation_charged']);
        $this->assertEquals(12980, $f['operating']);
        $this->assertEquals(6740, $f['crew_pool']);
        $this->assertEquals(6240, $f['owner_net']);
        $this->assertSame(1, $pl['open_count']);

        // التقرير يقرأ الشهر ولا يُنشئ مسيرًا ولا إغلاقًا.
        $this->assertSame(0, Payroll::count());
        $this->assertSame(0, MonthClosing::count());

        // البنود: لا "فرق عن لقطة الإغلاق" حين تتطابق السجلات.
        $lines = collect($this->reports()->statementLines($pl));
        $this->assertFalse($lines->contains('label', 'فرق عن لقطة الإغلاق'));
        $this->assertEquals(6240, $lines->last()['amount']);
        $this->assertEquals(-2400, $lines->firstWhere('label', 'إجمالي المصروفات')['amount']);

        // بعد الإغلاق: الأرقام نفسها من اللقطة.
        $closing = app(MonthClosingService::class)->close($this->owner, $this->month->year, $this->month->month);
        $this->assertEquals($closing->owner_net, 6240);

        $closed = app(OwnerReports::class)->profitLoss($this->owner, $this->month, $this->month->endOfMonth());
        $this->assertEquals(6240, $closed['figures']['owner_net']);
        $this->assertSame(1, $closed['closed_count']);

        // قارب واحد: لا عام عليه.
        $boat = app(OwnerReports::class)->profitLoss($this->owner, $this->month, $this->month->endOfMonth(), $this->boat);
        $this->assertEquals(1900, $boat['figures']['total_expenses']);
        $this->assertEquals(6740, $boat['figures']['owner_net']);
    }

    public function test_month_and_annual_summaries_follow_the_same_figures(): void
    {
        $this->seedMonth();

        $summary = $this->reports()->monthSummary($this->owner, $this->month->year, $this->month->month);
        $this->assertSame('open', $summary['status']);
        $this->assertEquals(6240, $summary['figures']['owner_net']);
        $this->assertSame('قارب التقارير', $summary['boats']['rows'][0]['boat']);
        $this->assertEquals(13480, $summary['boats']['rows'][0]['net_profit']);
        $this->assertSame('هامور التقارير', $summary['species']['rows'][0]['species']);
        $this->assertEquals(300, $summary['species']['rows'][0]['kg']);
        $this->assertEquals(15580, $summary['species']['rows'][0]['net']);

        $annual = $this->reports()->annual($this->owner, $this->month->year);
        $row = $annual['table']['rows'][$this->month->month - 1];
        $this->assertEquals(15580, $row['revenue']);
        $this->assertEquals(6240, $row['owner_net']);
        $this->assertSame('غير مُغلق', $row['status']);

        if ($this->month->month < 11) {
            $this->assertTrue($annual['table']['rows'][11]['_dim']);
        }

        app(MonthClosingService::class)->close($this->owner, $this->month->year, $this->month->month);
        $annual = app(OwnerReports::class)->annual($this->owner, $this->month->year);
        $this->assertSame('مُغلق', $annual['table']['rows'][$this->month->month - 1]['status']);
        $this->assertSame(1, $annual['closed']);
    }

    public function test_months_before_the_first_closing_do_not_count_salaries_that_will_never_post(): void
    {
        $this->seedMonth();
        Fisher::factory()->ownedBy($this->owner)->onBoat($this->boat)->create([
            'pay_type_id' => PayType::named(PayType::FIXED)->id,
            'fixed_salary' => 1800,
        ]);
        $before = $this->month->subMonth();

        // لا إغلاق بعد: الشهر السابق قابل للإغلاق فرواتبه ستُرحَّل.
        $this->assertEquals(1800, app(OwnerReports::class)->profitLoss($this->owner, $before, $before->endOfMonth())['figures']['pending_fixed']);

        app(MonthClosingService::class)->close($this->owner, $this->month->year, $this->month->month);

        // بعد أول إغلاق: الشهر قبله لن يُغلق، فلا رواتب "ستُرحَّل" — القارب بقسط أصله وحده.
        $f = app(OwnerReports::class)->profitLoss($this->owner, $before, $before->endOfMonth())['figures'];
        $this->assertEquals(0, $f['pending_fixed']);
        $this->assertEquals(0, $f['expenses']);
        $this->assertEquals(0, $f['depreciation_charged']);
        $this->assertEquals(0, $f['owner_net']);
    }

    public function test_boat_and_trip_profitability(): void
    {
        $this->seedMonth();

        $boats = $this->reports()->boatProfitability($this->owner, $this->month, $this->month->endOfMonth());
        $row = $boats['table']['rows'][0];
        $this->assertSame('قارب التقارير', $row['boat']);
        $this->assertSame(1, $row['trips']);
        $this->assertEquals(350, $row['caught_kg']);
        $this->assertEquals(300, $row['sold_kg']);
        $this->assertEquals(16000, $row['gross']);
        $this->assertEquals(420, $row['cut']);
        $this->assertEquals(15580, $row['revenue']);
        $this->assertEquals(1900, $row['expenses']);
        $this->assertEquals(200, $row['depreciation']);
        $this->assertEquals(13480, $row['net_profit']);
        $this->assertEquals(86.5, $row['margin']);
        $this->assertEquals(['expenses' => 500.0, 'depreciation' => 0.0], $boats['general']);
        $this->assertEquals(6240, $boats['owner_net']);

        $trips = $this->reports()->tripProfitability($this->owner, $this->month, $this->month->endOfMonth());
        $trip = $trips['table']['rows'][0];
        $this->assertSame('TR-RPT-1', $trip['trip']);
        $this->assertEquals(15580, $trip['revenue']);
        $this->assertEquals(400, $trip['expenses']);
        $this->assertEquals(15180, $trip['profit']);

        // رحلة ملغاة ورحلة مالك آخر لا تدخلان.
        Trip::factory()->forOwner($this->owner)->onBoat($this->boat)->create(['status' => Trip::CANCELLED, 'departure_time' => $this->month->addDays(5)]);
        $other = User::factory()->owner()->create();
        Trip::factory()->forOwner($other)->onBoat(Boat::factory()->ownedBy($other)->create())->create(['departure_time' => $this->month->addDays(5)]);
        $this->assertCount(1, $this->reports()->tripProfitability($this->owner, $this->month, $this->month->endOfMonth())['table']['rows']);
    }

    public function test_production_by_species_and_expenses_by_category(): void
    {
        $this->seedMonth();

        $production = $this->reports()->production($this->owner, $this->month, $this->month->endOfMonth());
        $row = $production['table']['rows'][0];
        $this->assertSame('هامور التقارير', $row['species']);
        $this->assertEquals(350, $row['caught_kg']);
        $this->assertEquals(200, $row['direct_kg']);
        $this->assertEquals(100, $row['dalal_kg']);
        $this->assertEquals(85.7, $row['sell_through']);
        $this->assertEquals(16000, $row['gross']);
        $this->assertEquals(15580, $row['net']);
        $this->assertEquals(53.33, $row['avg_price']);

        $all = $this->reports()->expensesByCategory($this->owner, $this->month, $this->month->endOfMonth());
        $this->assertEquals(2400, $all['total']);
        $this->assertEquals(100.0, $all['groups']['totals']['share']);
        $this->assertEquals(500, $this->reports()->expensesByCategory($this->owner, $this->month, $this->month->endOfMonth(), 'general')['total']);
        $this->assertEquals(1900, $this->reports()->expensesByCategory($this->owner, $this->month, $this->month->endOfMonth(), (string) $this->boat->id)['total']);
    }

    public function test_customer_and_vendor_statements_carry_an_opening_and_running_balance(): void
    {
        $customer = Customer::factory()->ofAccount($this->owner)->create(['name' => 'عميل الكشف']);
        foreach ([[1000, 400, 3], [2000, 2000, 12], [500, 0, 20]] as [$total, $paid, $day]) {
            Sale::factory()->create([
                'seller_id' => $this->owner->id, 'trip_id' => $this->trip->id, 'customer_id' => $customer->id,
                'subtotal' => $total, 'total' => $total, 'owner_net' => $total, 'paid_amount' => $paid,
                'sold_at' => $this->month->addDays($day)->setTime(9, 0),
            ]);
        }

        $statement = $this->reports()->customerStatement($this->owner, $customer, $this->month->addDays(10)->toDateString(), null);
        $this->assertEquals(600, $statement['opening']);
        $this->assertSame(2, $statement['count']);
        $this->assertEquals([600, 1100], array_column($statement['table']['rows'], 'balance'));
        $this->assertEquals(1100, $statement['closing']);
        $this->assertEquals(1100, $statement['all_time']);

        $vendor = Vendor::factory()->create(['owner_id' => $this->owner->id, 'name' => 'مورد الكشف']);
        $this->expense(700, vendor: $vendor, paid: 200);
        $this->expense(300, vendor: $vendor, paid: 300, on: $this->month->addDays(15));
        $vendorStatement = $this->reports()->vendorStatement($this->owner, $vendor, null, null);
        $this->assertNull($vendorStatement['opening']);
        $this->assertEquals([500, 500], array_column($vendorStatement['table']['rows'], 'balance'));

        $this->asOwner()->get("/admin/owner/reports/customer-statement/print?customer_id={$customer->id}")
            ->assertOk()->assertSee('عميل الكشف')->assertSee('1,100.00');

        // عميل مالك آخر وقاربه: 404.
        $other = User::factory()->owner()->create();
        $foreign = Customer::factory()->ofAccount($other)->create();
        $this->asOwner()->get("/admin/owner/reports/customer-statement?customer_id={$foreign->id}")->assertNotFound();
        $foreignBoat = Boat::factory()->ownedBy($other)->create();
        $this->asOwner()->get("/admin/owner/reports/trip-profitability?boat_id={$foreignBoat->id}")->assertNotFound();
    }
}
