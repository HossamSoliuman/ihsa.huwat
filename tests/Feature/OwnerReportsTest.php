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
use App\Services\Owner\PayrollService;
use App\Support\AmountInWords;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * O6 — تقارير المالك بنسق hispa: المركز بمجموعاته، وكل تقرير ويب + طباعة،
 * وأن أرقام قائمة الأرباح وملخص الشهر وربحية القوارب والإقفال السنوي تطابق
 * إغلاق الشهر، وربحية الرحلات، والإنتاج، والمصروفات حسب الفئة، وكميات
 * الأسماك، وكشوف العميل والمورد والطاقم.
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
        CatchRecord::factory()->create([
            'trip_id' => $this->trip->id, 'species_id' => $this->hamour->id,
            'quantity_kg' => 340, 'captain_kg' => 340, 'counted_kg' => 350, 'price_per_kg' => 40,
            'recorded_at' => $this->month->addDays(3)->toDateString(),
        ]);

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

    private function sharer(): Fisher
    {
        return Fisher::factory()->ownedBy($this->owner)->onBoat($this->boat)->create([
            'name' => 'بحار الحصة',
            'pay_type_id' => PayType::named(PayType::SHARE)->id,
            'profit_shares' => 1,
        ]);
    }

    private function reports(): OwnerReports
    {
        return app(OwnerReports::class);
    }

    private function range(): string
    {
        return 'from='.$this->month->toDateString().'&to='.$this->month->endOfMonth()->toDateString();
    }

    public function test_hub_lists_the_hispa_groups_and_every_report_renders_on_screen_and_in_print(): void
    {
        $this->seedMonth();
        $customer = Customer::factory()->ofAccount($this->owner)->create(['name' => 'عميل التقارير']);
        $vendor = Vendor::factory()->create(['owner_id' => $this->owner->id, 'name' => 'مورد التقارير']);
        $fisher = $this->sharer();
        app(MonthClosingService::class)->close($this->owner, $this->month->year, $this->month->month);

        $hub = $this->asOwner()->get('/admin/owner/reports')->assertOk();
        foreach (['التقارير المفصلة', 'تقارير تشغيلية', 'تقارير مالية', 'كشف الحسابات', 'تقارير الإقفال', 'تقارير إدارية', 'تقارير الأصول', 'تقرير الرحلات', 'تقرير المبيعات', 'الإقفال الشهري', 'كميات الأسماك', 'سجل الأصول التفصيلي'] as $text) {
            $hub->assertSee($text);
        }
        $hub->assertSee(route('panel.owner.reports.show', 'trip-report'), false)->assertSee(route('panel.owner.month-closings'), false);
        $this->asOwner()->get('/admin')->assertSee(route('panel.owner.reports'), false);

        $pages = [
            'trip-report' => $this->range(),
            'sales-report' => $this->range(),
            'profit-loss' => $this->range(),
            'month-summary' => $this->range(),
            'annual-summary' => 'year='.$this->month->year,
            'expenses-by-category' => $this->range(),
            'boat-profitability' => $this->range(),
            'trip-profitability' => $this->range(),
            'production' => $this->range(),
            'fish-quantity' => $this->range(),
            'customer-statement' => "customer_id={$customer->id}",
            'vendor-statement' => "vendor_id={$vendor->id}",
            'crew-statement' => "person_id={$fisher->id}",
        ];

        foreach ($pages as $report => $query) {
            $this->asOwner()->get("/admin/owner/reports/{$report}?{$query}")->assertOk()->assertSee(OwnerReports::REPORTS[$report]['title']);
            $this->asOwner()->get("/admin/owner/reports/{$report}/print?{$query}")->assertOk()->assertSee($this->owner->name)->assertSee('طباعة');
        }

        $this->asOwner()->get('/admin/owner/reports/boat-profitability?'.$this->range())->assertSee('قارب التقارير');
        $this->asOwner()->get('/admin/owner/reports/trip-profitability?'.$this->range())->assertSee('TR-RPT-1');
        $this->asOwner()->get('/admin/owner/reports/production?'.$this->range())->assertSee('هامور التقارير');
        $this->asOwner()->get('/admin/owner/reports/annual-summary')->assertSee('رابحة')->assertSee((string) $this->month->year);

        foreach (['customer-statement', 'vendor-statement', 'crew-statement'] as $statement) {
            $this->asOwner()->get("/admin/owner/reports/{$statement}")->assertOk()->assertSee('اختر من القائمة أعلاه لعرض كشف الحساب.');
            $this->asOwner()->get("/admin/owner/reports/{$statement}/print")->assertNotFound();
        }

        $this->asOwner()->get('/admin/owner/reports/profit-loss/export')->assertNotFound();
        $this->asOwner()->get('/admin/owner/reports/unknown')->assertNotFound();
        $this->actingAs($this->dalal)->get('/admin/owner/reports')->assertForbidden();
        $this->actingAs($this->dalal)->get('/admin/owner/reports/profit-loss')->assertForbidden();
    }

    public function test_profit_and_loss_follows_the_month_closing_and_writes_nothing(): void
    {
        $this->seedMonth();

        $f = $this->reports()->financials($this->owner, $this->month, $this->month->endOfMonth());

        $this->assertEquals(16000, $f['gross_sales']);
        $this->assertEquals(420, $f['commission_labor']);
        $this->assertEquals(15580, $f['net_owner_revenue']);
        $this->assertEquals(1900, $f['trip_expenses']);
        $this->assertEquals(500, $f['general_expenses']);
        $this->assertEquals(200, $f['depreciation']);
        $this->assertEquals(2600, $f['total_expenses']);
        $this->assertEquals(12980, $f['net_profit']);
        $this->assertEquals(6740, $f['crew_share']);
        $this->assertEquals(6240, $f['owner_share']);
        $this->assertEquals(50, $f['owner_percent']);
        $this->assertSame(0, $f['closed_count']);

        // التقرير يقرأ الشهر ولا يُنشئ مسيرًا ولا إغلاقًا.
        $this->assertSame(0, Payroll::count());
        $this->assertSame(0, MonthClosing::count());

        // بعد الإغلاق: الأرقام نفسها من اللقطة.
        $closing = app(MonthClosingService::class)->close($this->owner, $this->month->year, $this->month->month);
        $this->assertEquals(6240, $closing->owner_net);
        $closed = app(OwnerReports::class)->financials($this->owner, $this->month, $this->month->endOfMonth());
        $this->assertEquals(6240, $closed['owner_share']);
        $this->assertSame(1, $closed['closed_count']);

        // قارب واحد: لا عام عليه.
        $boat = app(OwnerReports::class)->financials($this->owner, $this->month, $this->month->endOfMonth(), $this->boat->id);
        $this->assertEquals(2100, $boat['total_expenses']);
        $this->assertEquals(6740, $boat['owner_share']);

        // الصفحة بتواريخ داخل الشهر تعرض الشهر كاملًا.
        $this->asOwner()->get('/admin/owner/reports/profit-loss?from='.$this->month->addDays(5)->toDateString().'&to='.$this->month->addDays(6)->toDateString())
            ->assertOk()->assertSee('ملخص الأرباح والخسائر')->assertSee('12,980.00')->assertSee($this->month->endOfMonth()->toDateString());
    }

    public function test_month_summary_lists_operating_and_general_expenses_that_add_up(): void
    {
        $this->seedMonth();
        $f = $this->reports()->financials($this->owner, $this->month, $this->month->endOfMonth());

        $expenses = $this->reports()->monthExpenses($this->owner, $this->month, $this->month->endOfMonth(), $f);
        $this->assertEquals(1900, array_sum(array_column($expenses['operating'], 'amount')));
        $this->assertEquals(500, array_sum(array_column($expenses['general'], 'amount')));

        $this->asOwner()->get('/admin/owner/reports/month-summary?'.$this->range())->assertOk()
            ->assertSee('المصروفات التشغيلية (الرحلات والصيانة)')->assertSee('توزيع الأرباح')
            ->assertSee('صافي الربح / الخسارة')->assertSee('12,980.00');
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
        $this->assertEquals(1800, app(OwnerReports::class)->financials($this->owner, $before, $before->endOfMonth())['pending_fixed']);

        app(MonthClosingService::class)->close($this->owner, $this->month->year, $this->month->month);

        // بعد أول إغلاق: الشهر قبله لن يُغلق، فلا رواتب "ستُرحَّل" — القارب بقسط أصله وحده.
        $f = app(OwnerReports::class)->financials($this->owner, $before, $before->endOfMonth());
        $this->assertEquals(0, $f['pending_fixed']);
        $this->assertEquals(0, $f['trip_expenses']);
        $this->assertEquals(0, $f['depreciation']);
        $this->assertEquals(0, $f['owner_share']);
    }

    public function test_an_idle_boat_keeps_its_fixed_salaries_only_in_months_that_will_still_close(): void
    {
        $idle = Boat::factory()->ownedBy($this->owner)->create(['name' => 'قارب خامل']);
        Fisher::factory()->ownedBy($this->owner)->onBoat($idle)->create([
            'pay_type_id' => PayType::named(PayType::FIXED)->id,
            'fixed_salary' => 900,
        ]);
        $now = CarbonImmutable::now()->startOfMonth();
        $old = $now->subMonths(3);

        // قبل أي إغلاق: الإغلاق يبدأ بالشهر الماضي، فالشهر الجاري سيُرحِّل الراتب،
        // وشهر أقدم بلا نشاط للقارب لا يُعدّ.
        $this->assertEquals(900, $this->reports()->financials($this->owner, $now, $now->endOfMonth(), $idle->id)['pending_fixed']);
        $this->assertEquals(0, $this->reports()->financials($this->owner, $old, $old->endOfMonth(), $idle->id)['pending_fixed']);

        app(MonthClosingService::class)->close($this->owner, $this->month->year, $this->month->month);

        // بعد الإغلاق: الشهر الجاري يلي آخر إغلاق — راتبه على القارب وإن خلا من النشاط.
        $f = $this->reports()->financials($this->owner, $now, $now->endOfMonth(), $idle->id);
        $this->assertEquals(900, $f['pending_fixed']);
        $this->assertEquals(-900, $f['net_profit']);
    }

    public function test_the_statement_shows_sales_changed_after_the_closing_as_a_difference(): void
    {
        $this->seedMonth();
        app(MonthClosingService::class)->close($this->owner, $this->month->year, $this->month->month);

        // بيع مباشر أُضيف لشهر مُغلق: الإجمالي من الفواتير، وصافي الإيراد من اللقطة.
        Sale::factory()->create([
            'seller_id' => $this->owner->id, 'trip_id' => $this->trip->id,
            'subtotal' => 700, 'total' => 700, 'owner_net' => 700,
            'sold_at' => $this->month->addDays(20)->setTime(10, 0),
        ]);

        $f = $this->reports()->financials($this->owner, $this->month, $this->month->endOfMonth());
        $this->assertEquals(16700, $f['gross_sales']);
        $this->assertEquals(15580, $f['net_owner_revenue']);
        $this->assertEquals(-700, $f['revenue_adjustment']);
        $this->assertEquals($f['net_owner_revenue'], round($f['gross_sales'] - $f['commission_labor'] + $f['revenue_adjustment'], 2));

        $this->asOwner()->get('/admin/owner/reports/month-summary?'.$this->range())->assertOk()->assertSee('فرق عن لقطة الإغلاق');
        $this->asOwner()->get('/admin/owner/reports/profit-loss/print?'.$this->range())->assertOk()->assertSee('فرق عن لقطة الإغلاق');
    }

    public function test_open_months_are_flagged_as_provisional_with_a_link_to_the_closings(): void
    {
        $this->seedMonth();

        foreach (['profit-loss', 'month-summary', 'boat-profitability'] as $report) {
            $this->asOwner()->get("/admin/owner/reports/{$report}?".$this->range())->assertOk()
                ->assertSee('الأشهر المقفلة في الفترة: 0 من 1')->assertSee(route('panel.owner.month-closings'), false);
        }

        app(MonthClosingService::class)->close($this->owner, $this->month->year, $this->month->month);
        $this->asOwner()->get('/admin/owner/reports/profit-loss?'.$this->range())->assertOk()->assertDontSee('الأشهر المقفلة في الفترة');
    }

    public function test_reports_link_to_their_records_and_to_each_other(): void
    {
        $this->seedMonth();
        $fisher = $this->sharer();
        $closing = app(MonthClosingService::class)->close($this->owner, $this->month->year, $this->month->month);
        $payroll = $closing->boats()->first()->payroll;
        $customer = Customer::factory()->ofAccount($this->owner)->create();
        $sale = Sale::factory()->create(['seller_id' => $this->owner->id, 'trip_id' => $this->trip->id, 'customer_id' => $customer->id, 'sold_at' => $this->month->addDays(5)]);
        $vendor = Vendor::factory()->create(['owner_id' => $this->owner->id]);
        $expense = $this->expense(250, vendor: $vendor);
        $range = ['from' => $this->month->toDateString(), 'to' => $this->month->endOfMonth()->toDateString()];

        // روابط باستعلام: `&` تُطبع `&amp;`، فتُقارن مُهرَّبة.
        $this->asOwner()->get('/admin/owner/reports/production?'.$this->range())
            ->assertSee(route('panel.owner.reports.show', ['report' => 'fish-quantity', 'fish_id' => $this->hamour->id] + $range))
            ->assertSee(route('panel.owner.reports'), false);
        $this->asOwner()->get('/admin/owner/reports/boat-profitability?'.$this->range())
            ->assertSee(route('panel.owner.reports.show', ['report' => 'trip-profitability', 'boat_id' => $this->boat->id] + $range));
        $this->asOwner()->get('/admin/owner/reports/expenses-by-category?'.$this->range())
            ->assertSee(route('panel.owner.expenses', ['category' => $expense->expense_category_id] + $range));
        $this->asOwner()->get("/admin/owner/reports/customer-statement?customer_id={$customer->id}")->assertSee(route('panel.owner.sales.show', $sale->id), false);
        $this->asOwner()->get("/admin/owner/reports/vendor-statement?vendor_id={$vendor->id}")->assertSee(route('panel.owner.expenses', ['search' => $expense->expense_number]), false);
        $this->asOwner()->get("/admin/owner/reports/crew-statement?person_id={$fisher->id}")->assertSee(route('panel.owner.payrolls.show', $payroll->id), false);
    }

    public function test_crew_share_is_distributed_and_the_crew_statement_follows_the_payroll(): void
    {
        $this->seedMonth();
        $fisher = $this->sharer();

        $f = $this->reports()->financials($this->owner, $this->month, $this->month->endOfMonth());
        $this->assertSame(1, $f['crew_count']);
        $this->assertSame('بحار الحصة', $f['crew_distribution'][0]['name']);
        $this->assertEquals(6740, $f['crew_distribution'][0]['due']);
        $this->assertEquals(6740, $f['per_fisherman']);

        // مسير الشهر المفتوح يتقادم ببيع لاحق (التقرير لا يحدّثه)، والتوزيع يتبع
        // نصيب الطاقم لا سطر المسير: مجموعه = حصة البحارة.
        app(PayrollService::class)->generate($this->owner, $this->boat, $this->month->year, $this->month->month);
        Sale::factory()->create([
            'seller_id' => $this->owner->id, 'trip_id' => $this->trip->id,
            'subtotal' => 1000, 'total' => 1000, 'owner_net' => 1000,
            'sold_at' => $this->month->addDays(12)->setTime(10, 0),
        ]);
        $stale = app(OwnerReports::class)->financials($this->owner, $this->month, $this->month->endOfMonth());
        $this->assertEquals(7240, $stale['crew_share']);
        $this->assertEquals($stale['crew_share'], array_sum(array_column($stale['crew_distribution'], 'due')));

        app(MonthClosingService::class)->close($this->owner, $this->month->year, $this->month->month);

        $statement = $this->reports()->crewStatement($fisher, null, null);
        $this->assertCount(1, $statement['rows']);
        $this->assertSame($this->month->format('m / Y'), $statement['rows'][0]['period']);
        $this->assertEquals(7240, $statement['totals']['due']);
        $this->assertEquals(7240, $statement['totals']['unpaid']);
        $this->assertEquals(0, $statement['totals']['paid']);

        // خارج الفترة: لا سطور.
        $this->assertSame([], $this->reports()->crewStatement($fisher, $this->month->addMonth()->toDateString(), null)['rows']);

        // فرد مالك آخر: 404.
        $other = User::factory()->owner()->create();
        $foreign = Fisher::factory()->ownedBy($other)->create();
        $this->asOwner()->get("/admin/owner/reports/crew-statement?person_id={$foreign->id}")->assertNotFound();
    }

    public function test_trip_report_and_sales_report(): void
    {
        $this->seedMonth();
        $this->trip->update(['return_time' => $this->month->addDays(4)->setTime(18, 0), 'license_number' => 'LIC-RPT']);

        $report = $this->reports()->tripReport($this->owner, $this->month, $this->month->endOfMonth());
        $row = $report['rows'][0];
        $this->assertSame('TR-RPT-1', $row['number']);
        $this->assertSame('LIC-RPT', $row['license_number']);
        $this->assertSame(1, $row['items']);
        $this->assertEquals(350, $row['weight']);
        $this->assertSame(3, $row['days']);
        $this->assertEquals(16000, $row['gross_revenue']);
        $this->assertEquals(15180, $row['net_profit']);
        $this->assertSame(1, $report['statistics']['total_trips']);

        // بلا فترة: كل الرحلات (كجدول hispa)، والحالة تصفّي.
        $this->assertCount(1, $this->reports()->tripReport($this->owner, null, null)['rows']);
        $this->assertCount(0, $this->reports()->tripReport($this->owner, null, null, Trip::CANCELLED)['rows']);

        // فواتير بيعك المباشر وحدها — بيع الدلال في فاتورته هو.
        $sales = $this->reports()->salesReport($this->owner, $this->month, $this->month->endOfMonth());
        $this->assertCount(1, $sales['rows']);
        $this->assertEquals(200, $sales['rows'][0]['weight']);
        $this->assertEquals(10000, $sales['statistics']['total_revenue']);
        $this->assertEquals(10000, $sales['statistics']['net_owner']);

        $this->asOwner()->get('/admin/owner/reports/trip-report')->assertOk()->assertSee('TR-RPT-1')->assertSee('LIC-RPT');
        $this->asOwner()->get('/admin/owner/reports/sales-report/print?'.$this->range())->assertOk()->assertSee('تقرير المبيعات')->assertSee('10,000.00');
    }

    public function test_boat_and_trip_profitability(): void
    {
        $this->seedMonth();
        Boat::factory()->ownedBy($this->owner)->create(['name' => 'قارب بلا نشاط']);

        $boats = $this->reports()->boatProfitability($this->owner, $this->month, $this->month->endOfMonth());
        $this->assertCount(2, $boats['rows']);
        $row = $boats['rows'][0];
        $this->assertSame('قارب التقارير', $row['boat_name']);
        $this->assertEquals(16000, $row['gross_sales']);
        $this->assertEquals(15580, $row['net_sales']);
        $this->assertEquals(2100, $row['expenses']);
        $this->assertEquals(13480, $row['net_profit']);
        $this->assertEquals(86.52, $row['margin']);
        $this->assertEquals(0, $boats['rows'][1]['net_profit']);
        $this->assertEquals(13480, $boats['totals']['net_profit']);

        $trips = $this->reports()->tripProfitability($this->owner, $this->month, $this->month->endOfMonth());
        $trip = $trips['rows'][0];
        $this->assertSame('TR-RPT-1', $trip['number']);
        $this->assertEquals(16000, $trip['gross_sales']);
        $this->assertEquals(15580, $trip['net_sales']);
        $this->assertEquals(400, $trip['expenses']);
        $this->assertEquals(15180, $trip['net_profit']);

        // رحلة ملغاة تظهر بحالتها (كما في hispa)، ورحلة مالك آخر لا تدخل.
        Trip::factory()->forOwner($this->owner)->onBoat($this->boat)->create(['status' => Trip::CANCELLED, 'departure_time' => $this->month->addDays(5)]);
        $other = User::factory()->owner()->create();
        Trip::factory()->forOwner($other)->onBoat(Boat::factory()->ownedBy($other)->create())->create(['departure_time' => $this->month->addDays(5)]);
        $rows = $this->reports()->tripProfitability($this->owner, $this->month, $this->month->endOfMonth())['rows'];
        $this->assertCount(2, $rows);
        $this->assertSame('ملغاة', $rows[0]['status_label']);

        $foreignBoat = Boat::factory()->ownedBy($other)->create();
        $this->asOwner()->get("/admin/owner/reports/trip-profitability?boat_id={$foreignBoat->id}")->assertNotFound();
    }

    public function test_production_expenses_by_category_and_fish_quantity(): void
    {
        $this->seedMonth();

        $row = $this->reports()->production($this->owner, $this->month, $this->month->endOfMonth())[0];
        $this->assertSame('هامور التقارير', $row['fish_name']);
        $this->assertEquals(350, $row['caught_weight']);
        $this->assertEquals(14000, $row['caught_value']);
        $this->assertEquals(300, $row['sold_weight']);
        $this->assertEquals(16000, $row['sold_value']);

        $all = $this->reports()->expenseRows($this->owner, $this->month, $this->month->endOfMonth());
        $this->assertCount(3, $all);
        $this->assertEquals(1500, $all[0]['amount']);
        $this->assertEquals(1900, array_sum(array_column($this->reports()->expenseRows($this->owner, $this->month, $this->month->endOfMonth(), (string) $this->boat->id), 'amount')));

        $stocks = $this->reports()->fishQuantity($this->owner, $this->month, $this->month->endOfMonth());
        $this->assertCount(1, $stocks);
        $this->assertEquals(350, $stocks[0]['weight']);
        $this->assertEquals(14000, $stocks[0]['total']);
        $this->assertCount(0, $this->reports()->fishQuantity($this->owner, $this->month, $this->month->endOfMonth(), speciesId: Species::factory()->create()->id));

        $this->asOwner()->get('/admin/owner/reports/fish-quantity?'.$this->range().'&trip_id='.$this->trip->id)->assertOk()->assertSee('هامور التقارير')->assertSee('14,000.00');
    }

    public function test_customer_and_vendor_statements(): void
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
        $this->assertSame(['total_orders' => 2, 'total_purchases' => 2500.0, 'total_paid' => 2000.0, 'total_remaining' => 500.0], $statement['statistics']);
        $this->assertEquals([500, 2000], array_column($statement['rows'], 'total'));

        $vendor = Vendor::factory()->create(['owner_id' => $this->owner->id, 'name' => 'مورد الكشف']);
        $this->expense(700, vendor: $vendor, paid: 200);
        $this->expense(300, vendor: $vendor, paid: 300, on: $this->month->addDays(15));
        $vendorStatement = $this->reports()->vendorStatement($this->owner, $vendor, null, null);
        $this->assertEquals(1000, $vendorStatement['total_expenses']);
        $this->assertEquals(500, $vendorStatement['total_due']);
        $this->assertTrue($vendorStatement['rows'][0]['is_paid']);

        $this->asOwner()->get("/admin/owner/reports/customer-statement/print?customer_id={$customer->id}")
            ->assertOk()->assertSee('كشف حساب العميل')->assertSee('عميل الكشف')->assertSee('3,500.00');

        // عميل مالك آخر: 404.
        $other = User::factory()->owner()->create();
        $foreign = Customer::factory()->ofAccount($other)->create();
        $this->asOwner()->get("/admin/owner/reports/customer-statement?customer_id={$foreign->id}")->assertNotFound();
    }

    public function test_annual_summary_counts_closed_months_only(): void
    {
        $this->seedMonth();
        $this->sharer();

        $this->assertSame([], $this->reports()->closedYears($this->owner));

        app(MonthClosingService::class)->close($this->owner, $this->month->year, $this->month->month);

        $years = app(OwnerReports::class)->closedYears($this->owner);
        $this->assertCount(1, $years);
        $summary = $years[0]['summary'];
        $this->assertSame($this->month->year, $years[0]['year']);
        $this->assertSame(1, $summary['closed_count']);
        $this->assertEquals(16000, $summary['totals']['gross_sales']);
        $this->assertEquals(12980, $summary['totals']['net_profit']);
        $this->assertEquals(6740, $summary['totals']['crew_share']);
        $this->assertNotNull($summary['months'][$this->month->month]);

        $analysis = app(OwnerReports::class)->annualAnalysis($this->owner, $summary);
        $this->assertTrue($analysis['analysis']['is_profitable']);
        $this->assertSame($this->month->month, $analysis['analysis']['best']['month']);
        $this->assertEquals(16000, $analysis['sales']['totals']['gross']);
        $this->assertSame(2, $analysis['sales']['totals']['invoices']);
        $this->assertEquals(350, $analysis['catch']['total_weight']);
        $this->assertSame(1, $analysis['payroll']['crew_count']);
        $this->assertEquals(6740, $analysis['crew_members'][0]['earned']);
        $this->assertEquals(2400, array_sum(array_column($analysis['expenses_by_category'], 'total')));

        $this->asOwner()->get('/admin/owner/reports/annual-summary/print?year='.$this->month->year)->assertOk()
            ->assertSee('تفصيل الأشهر')->assertSee('أهم المؤشرات')->assertSee(AmountInWords::riyals(12980));

        // سنة بلا شهر مُغلق: لا تقرير (لا "خاسرة" بصافي صفر).
        $this->asOwner()->get('/admin/owner/reports/annual-summary/print?year='.($this->month->year - 1))->assertNotFound();

        // تاريخ غير موجود يُتجاهل فتعود الفترة الافتراضية.
        $this->asOwner()->get('/admin/owner/reports/production?from=2026-02-31&to=2026-02-31')->assertOk()
            ->assertSee(CarbonImmutable::now()->startOfMonth()->toDateString());
    }

    public function test_amount_in_words(): void
    {
        $this->assertSame('ثلاثة عشر ألف وسبعمائة وثلاثون ريال سعودي فقط لا غير', AmountInWords::riyals(13730));
        $this->assertSame('ألفان ومائة ريال سعودي وخمسون هللة فقط لا غير', AmountInWords::riyals(2100.5));
        $this->assertStringStartsWith('سالب ', AmountInWords::riyals(-5));
    }
}
