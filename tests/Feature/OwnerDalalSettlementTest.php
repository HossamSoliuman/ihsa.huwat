<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\DalalInvoiceReview;
use App\Models\DalalPartnership;
use App\Models\DalalPayout;
use App\Models\Sale;
use App\Models\Species;
use App\Models\Trip;
use App\Models\User;
use App\Services\Dalal\DalalAccounts;
use App\Services\Notifications\Notifier;
use App\Services\Sales\SaleService;
use App\Services\Trips\TripService;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * O5 — تسوية المالك مع الدلال: فاتورة الدلال عند المالك (سطوره وحدها)
 * بمراجعتها قبولًا ورفضًا وردّ الدلال، وحالة سدادها من توزيع الدفعات على
 * الأقدم أوّلًا، وحساب الدلال وكشفه واستلام المالك، ومخزون الدلالين من
 * دفتر المخزون، وأداء الدلالين، وتحذير إغلاق الشهر.
 */
class OwnerDalalSettlementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $dalal;

    private Species $hamour;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        $this->owner = User::factory()->owner()->create(['name' => 'مالك التسوية']);
        $this->dalal = User::factory()->dalal()->create(['name' => 'دلال التسوية']);
        $this->hamour = Species::factory()->create(['name_ar' => 'هامور التسوية']);

        DalalPartnership::factory()->accepted()->create(['owner_id' => $this->owner->id, 'dalal_id' => $this->dalal->id, 'commission_pct' => 5, 'wage_pct' => 2]);
    }

    /**
     * رحلة معدودة للمالك على قارب باسم، ثم يرسل منها إلى الدلال.
     */
    private function consign(User $owner, float $kg, string $tripNumber, ?User $dalal = null, ?Species $species = null, string $boatName = 'قارب التسوية'): Trip
    {
        $species ??= $this->hamour;
        $boat = Boat::factory()->ownedBy($owner)->create(['name' => $boatName]);
        $trip = Trip::factory()->onBoat($boat)->forOwner($owner)->create([
            'trip_number' => $tripNumber,
            'status' => Trip::AWAITING_APPROVAL,
            'counted_at' => now(),
        ]);
        CatchRecord::create(['trip_id' => $trip->id, 'species_id' => $species->id, 'quantity_kg' => 500, 'counted_kg' => 500, 'recorded_at' => now()->toDateString()]);

        app(TripService::class)->openForSale($trip->fresh());
        app(SaleService::class)->consign($owner, ['trip_id' => $trip->id, 'dalal_id' => ($dalal ?? $this->dalal)->id, 'items' => [['species_id' => $species->id, 'weight_kg' => $kg]]]);

        return $trip;
    }

    private function sell(float $kg, float $price, ?Trip $trip = null, ?User $dalal = null, ?Species $species = null): Sale
    {
        return app(SaleService::class)->sellFromStock($dalal ?? $this->dalal, [
            'items' => [['species_id' => ($species ?? $this->hamour)->id, 'weight_kg' => $kg, 'price_per_kg' => $price, 'trip_id' => $trip?->id]],
        ]);
    }

    private function asOwner(): static
    {
        return $this->actingAs($this->owner);
    }

    public function test_a_dalal_sale_opens_one_review_per_owner_who_sees_only_their_lines(): void
    {
        $mine = $this->consign($this->owner, 60, 'TR-2026-5001');
        $other = User::factory()->owner()->create(['name' => 'مالك آخر']);
        $hamra = Species::factory()->create(['name_ar' => 'حمرا الآخر']);
        $theirs = $this->consign($other, 40, 'TR-2026-5002', null, $hamra, 'قارب الآخر');

        $sale = app(SaleService::class)->sellFromStock($this->dalal, ['items' => [
            ['species_id' => $this->hamour->id, 'weight_kg' => 20, 'price_per_kg' => 50],
            ['species_id' => $hamra->id, 'weight_kg' => 10, 'price_per_kg' => 30],
        ]]);

        $this->assertSame(2, DalalInvoiceReview::where('sale_id', $sale->id)->where('status', DalalInvoiceReview::PENDING)->count());

        // 20 × 50 = 1000 − 5% − 2% = 930 صافيه وحده.
        $this->asOwner()->get("/admin/owner/dalal-invoices/{$sale->id}")
            ->assertOk()
            ->assertSee($sale->invoice_number)
            ->assertSee('هامور التسوية')
            ->assertSee('TR-2026-5001')
            ->assertSee('930.00')
            ->assertDontSee('حمرا الآخر')
            ->assertDontSee('TR-2026-5002');

        $this->asOwner()->get('/admin/owner/dalal-invoices')
            ->assertOk()
            ->assertSee($sale->invoice_number)
            ->assertSee('قيد المراجعة')
            ->assertSee('غير مسدَّدة');

        // مالك لا سطر له في الفاتورة لا يراها.
        $this->actingAs(User::factory()->owner()->create())->get("/admin/owner/dalal-invoices/{$sale->id}")->assertNotFound();
        $this->assertTrue($mine->exists && $theirs->exists);
    }

    public function test_owner_rejects_with_a_reason_the_dalal_replies_and_the_owner_accepts(): void
    {
        $this->consign($this->owner, 60, 'TR-2026-5011');
        $sale = $this->sell(20, 40);

        $this->asOwner()->post("/admin/owner/dalal-invoices/{$sale->id}/reject", [])->assertSessionHasErrors('reason');

        $this->asOwner()->post("/admin/owner/dalal-invoices/{$sale->id}/reject", ['reason' => 'السعر أقل من سعر السوق'])
            ->assertRedirect("/admin/owner/dalal-invoices/{$sale->id}");

        $review = DalalInvoiceReview::where('sale_id', $sale->id)->sole();
        $this->assertSame(DalalInvoiceReview::REJECTED, $review->status);
        $this->assertSame($this->owner->id, $review->reviewed_by);

        $notice = AppNotification::where('user_id', $this->dalal->id)->latest('id')->first();
        $this->assertSame(Notifier::INVOICE_REJECTED, $notice->type->name);
        $this->assertSame(['target' => 'sale', 'sale_id' => $sale->id], array_intersect_key($notice->data, ['target' => 1, 'sale_id' => 1]));
        $this->actingAs($this->dalal)->post("/admin/notifications/{$notice->id}/read")->assertRedirect(route('panel.dalal.sales.show', $sale->id));

        // مرفوضة لا تُرفض ثانيةً؛ الدلال يراها ويردّ.
        $this->asOwner()->post("/admin/owner/dalal-invoices/{$sale->id}/reject", ['reason' => 'مرة أخرى'])->assertSessionHasErrors('reason');
        $this->actingAs($this->dalal)->get("/admin/dalal/sales/{$sale->id}")->assertOk()->assertSee('السعر أقل من سعر السوق')->assertSee('مرفوضة');
        $this->actingAs($this->dalal)->get('/admin/dalal/sales')->assertOk()->assertSee('رفض مالك');

        $this->actingAs($this->dalal)->post("/admin/dalal/sales/{$sale->id}/reviews/{$review->id}/reply", ['reply' => 'بيع في آخر المزاد بسعر اليوم'])
            ->assertRedirect(route('panel.dalal.sales.show', $sale->id));
        $this->assertSame(DalalInvoiceReview::PENDING, $review->fresh()->status);
        $reply = AppNotification::where('user_id', $this->owner->id)->latest('id')->first();
        $this->assertSame(Notifier::INVOICE_REPLIED, $reply->type->name);
        $this->asOwner()->post("/admin/notifications/{$reply->id}/read")->assertRedirect(route('panel.owner.dalal-invoices.show', $sale->id));

        // لا ردّ على غير المرفوضة، ودلال آخر لا يصل للمراجعة.
        $this->actingAs($this->dalal)->post("/admin/dalal/sales/{$sale->id}/reviews/{$review->id}/reply", ['reply' => 'ثانية'])->assertSessionHasErrors('reply');
        $this->actingAs(User::factory()->dalal()->create())->post("/admin/dalal/sales/{$sale->id}/reviews/{$review->id}/reply", ['reply' => 'x'])->assertNotFound();

        $this->asOwner()->post("/admin/owner/dalal-invoices/{$sale->id}/accept")->assertRedirect();
        $this->assertSame(DalalInvoiceReview::ACCEPTED, $review->fresh()->status);
        $this->assertSame(Notifier::INVOICE_ACCEPTED, AppNotification::where('user_id', $this->dalal->id)->latest('id')->first()->type->name);

        // القبول نهائي.
        $this->asOwner()->post("/admin/owner/dalal-invoices/{$sale->id}/accept")->assertSessionHasErrors('review');
        $this->asOwner()->post("/admin/owner/dalal-invoices/{$sale->id}/reject", ['reason' => 'متأخر'])->assertSessionHasErrors('reason');
    }

    public function test_accept_all_takes_pending_invoices_only(): void
    {
        $this->consign($this->owner, 90, 'TR-2026-5021');
        $first = $this->sell(10, 40);
        $second = $this->sell(10, 40);
        $third = $this->sell(10, 40);
        DalalInvoiceReview::where('sale_id', $third->id)->update(['status' => DalalInvoiceReview::REJECTED, 'reason' => 'خطأ']);

        $this->asOwner()->post('/admin/owner/dalal-invoices/accept-all', ['dalal_id' => $this->dalal->id])->assertRedirect()->assertSessionHas('status', 'قُبلت 2 فاتورة قيد المراجعة.');

        $this->assertSame(DalalInvoiceReview::ACCEPTED, DalalInvoiceReview::where('sale_id', $first->id)->value('status'));
        $this->assertSame(DalalInvoiceReview::ACCEPTED, DalalInvoiceReview::where('sale_id', $second->id)->value('status'));
        $this->assertSame(DalalInvoiceReview::REJECTED, DalalInvoiceReview::where('sale_id', $third->id)->value('status'));

        // دلال لا تعامل معه = 404.
        $this->asOwner()->post('/admin/owner/dalal-invoices/accept-all', ['dalal_id' => User::factory()->dalal()->create()->id])->assertNotFound();
    }

    public function test_payouts_settle_the_oldest_invoices_first(): void
    {
        $this->consign($this->owner, 100, 'TR-2026-5031');
        Carbon::setTestNow(now()->subDays(2));
        $old = $this->sell(10, 100); // 1000 − 7% = 930
        Carbon::setTestNow();
        $new = $this->sell(10, 100); // 930

        app(DalalAccounts::class)->recordPayout($this->dalal, $this->owner, ['amount' => 1200]);

        $rows = $this->asOwner()->get('/admin/owner/dalal-invoices')->assertOk()->viewData('invoices')->getCollection()->keyBy('sale_id');
        $this->assertSame('paid', $rows[$old->id]['payment']);
        $this->assertSame(930.0, $rows[$old->id]['settled']);
        $this->assertSame('partial', $rows[$new->id]['payment']);
        $this->assertSame(270.0, $rows[$new->id]['settled']);
        $this->assertSame(660.0, $rows[$new->id]['outstanding']);

        $this->asOwner()->get('/admin/owner/dalal-invoices?payment=partial')->assertOk()->assertSee($new->invoice_number)->assertDontSee($old->invoice_number);
    }

    public function test_owner_records_a_receipt_capped_at_the_balance_and_deletes_only_their_own(): void
    {
        $this->consign($this->owner, 60, 'TR-2026-5041');
        $this->sell(10, 100); // 930 مستحق
        $byDalal = app(DalalAccounts::class)->recordPayout($this->dalal, $this->owner, ['amount' => 300]);

        $this->asOwner()->get('/admin/owner/dalal-accounts')->assertOk()->assertSee('دلال التسوية')->assertSee('630.00');

        $this->asOwner()->post("/admin/owner/dalal-accounts/{$this->dalal->id}/receipts", ['amount' => 700])->assertSessionHasErrors('amount');

        $this->asOwner()->post("/admin/owner/dalal-accounts/{$this->dalal->id}/receipts", ['amount' => 200, 'reference' => 'TRX-77', 'paid_at' => now()->toDateString()])
            ->assertRedirect("/admin/owner/dalal-accounts/{$this->dalal->id}");

        $receipt = DalalPayout::where('reference', 'TRX-77')->sole();
        $this->assertSame($this->owner->id, $receipt->user_id);
        $this->assertTrue($receipt->recordedByOwner());
        // الدلال يرى الرقم نفسه.
        $this->assertSame(430.0, app(DalalAccounts::class)->dueTo($this->dalal, $this->owner));
        $this->assertSame(Notifier::RECEIPT_RECORDED, AppNotification::where('user_id', $this->dalal->id)->latest('id')->first()->type->name);
        $this->actingAs($this->dalal)->get('/admin/dalal/owners')->assertOk()->assertSee('سجّله المالك استلامًا')->assertSee('TRX-77');

        $this->asOwner()->delete("/admin/owner/dalal-accounts/{$this->dalal->id}/receipts/{$byDalal->id}")->assertSessionHasErrors('payout');
        $this->asOwner()->delete("/admin/owner/dalal-accounts/{$this->dalal->id}/receipts/{$receipt->id}")->assertRedirect();
        $this->assertModelMissing($receipt);
        $this->assertModelExists($byDalal);
    }

    public function test_the_statement_runs_a_balance_and_prints(): void
    {
        $this->consign($this->owner, 60, 'TR-2026-5051');
        Carbon::setTestNow(now()->subDays(10));
        $old = $this->sell(10, 100); // 930
        app(DalalAccounts::class)->recordPayout($this->dalal, $this->owner, ['amount' => 500]);
        Carbon::setTestNow();
        $new = $this->sell(5, 100); // 465

        $statement = $this->asOwner()->get("/admin/owner/dalal-accounts/{$this->dalal->id}")->assertOk()
            ->assertSee($old->invoice_number)->assertSee($new->invoice_number)->assertSee('دفعة سجّلها الدلال')
            ->viewData('statement');
        $this->assertSame([930.0, 430.0, 895.0], $statement['entries']->pluck('balance')->all());

        $from = now()->subDays(3)->toDateString();
        $window = $this->asOwner()->get("/admin/owner/dalal-accounts/{$this->dalal->id}?from={$from}")->assertOk()->assertSee('رصيد افتتاحي')->viewData('statement');
        $this->assertSame(430.0, $window['opening']);
        $this->assertSame(895.0, $window['totals']['closing']);

        $this->asOwner()->get("/admin/owner/dalal-accounts/{$this->dalal->id}/print?from={$from}")->assertOk()->assertSee('كشف حساب دلال')->assertSee('430.00');
        $this->asOwner()->get("/admin/owner/dalal-invoices/{$new->id}/print")->assertOk()->assertSee('كشف فاتورة دلال')->assertSee('465.00');

        $this->asOwner()->get('/admin/owner/dalal-accounts/'.User::factory()->dalal()->create()->id)->assertNotFound();
    }

    public function test_dalal_stock_by_boat_and_trip_comes_from_the_ledger(): void
    {
        $trip = $this->consign($this->owner, 60, 'TR-2026-5061');
        $sale = $this->sell(20, 50, $trip);

        $page = $this->asOwner()->get('/admin/owner/dalal-stock')->assertOk()
            ->assertSee('قارب التسوية')->assertSee('TR-2026-5061')->assertSee('دلال التسوية');
        $totals = $page->viewData('totals');
        $this->assertSame(60.0, $totals['consigned_kg']);
        $this->assertSame(20.0, $totals['sold_kg']);
        $this->assertSame(40.0, $totals['remaining_kg']);
        $this->assertSame(33.3, $totals['sell_through']);
        $this->assertSame(930.0, $totals['owner_net']);

        $this->asOwner()->get("/admin/owner/dalal-stock/trips/{$trip->id}")->assertOk()
            ->assertSee('هامور التسوية')->assertSee($sale->invoice_number)->assertSee('50.00');

        $this->actingAs(User::factory()->owner()->create())->get("/admin/owner/dalal-stock/trips/{$trip->id}")->assertNotFound();
        $this->asOwner()->get('/admin/owner/dalal-stock?holding=1&dalal_id='.$this->dalal->id)->assertOk()->assertSee('TR-2026-5061');
    }

    public function test_performance_ranks_dalals_and_compares_one_species_price(): void
    {
        $second = User::factory()->dalal()->create(['name' => 'دلال ثانٍ']);
        $this->consign($this->owner, 60, 'TR-2026-5071');
        $this->consign($this->owner, 60, 'TR-2026-5072', $second, null, 'قارب ثانٍ');
        $this->sell(20, 50);
        $this->sell(20, 60, null, $second);
        app(DalalAccounts::class)->recordPayout($second, $this->owner, ['amount' => 1200]);

        $data = $this->asOwner()->get('/admin/owner/dalal-performance?period=month')->assertOk()
            ->assertSee('دلال ثانٍ')->assertSee('صافيك من كل دلال')->assertSee('hawatChart')
            ->viewData('highlights');

        // الثاني بلا اتفاق: لا عمولة، فصافيه 1200 وأعلى سعرًا (60).
        $this->assertSame('دلال ثانٍ', $data['top_net']['dalal']);
        $this->assertSame('دلال ثانٍ', $data['best_price']['dalal']);
        $this->assertSame('دلال التسوية', $data['highest_balance']['dalal']);

        $this->asOwner()->get('/admin/owner/dalal-performance?period=custom&from=2020-01-01&to=2020-01-31')->assertOk()->assertSee('لا مبيعات في هذه الفترة');
        $this->asOwner()->get('/admin/owner/dalal-performance?period=all')->assertOk();
    }

    public function test_owner_pages_are_for_owners_and_the_home_card_shows(): void
    {
        foreach (['dalal-invoices', 'dalal-accounts', 'dalal-stock', 'dalal-performance'] as $page) {
            $this->actingAs($this->dalal)->get("/admin/owner/{$page}")->assertForbidden();
            $this->asOwner()->get("/admin/owner/{$page}")->assertOk();
        }

        $this->consign($this->owner, 60, 'TR-2026-5081');
        $sale = $this->sell(10, 40);

        $this->asOwner()->get('/admin')->assertOk()
            ->assertSee('تسوية الدلالين')->assertSee('1 فاتورة بانتظار مراجعتك')
            ->assertSee(route('panel.owner.dalal-invoices'), false)
            ->assertSee(route('panel.owner.dalal-performance'), false);

        // إشعار "بيع من مصيدك" يفتح فاتورته.
        $sold = AppNotification::where('user_id', $this->owner->id)->whereHas('type', fn ($q) => $q->where('name', Notifier::DALAL_SOLD))->sole();
        $this->asOwner()->post("/admin/notifications/{$sold->id}/read")->assertRedirect(route('panel.owner.dalal-invoices.show', $sale->id));
    }

    public function test_month_closing_preview_warns_about_unreviewed_dalal_invoices(): void
    {
        $this->consign($this->owner, 60, 'TR-2026-5091');
        $lastMonth = now()->startOfMonth()->subMonth();

        Carbon::setTestNow($lastMonth->copy()->addDays(5));
        $sale = $this->sell(10, 40);
        Carbon::setTestNow();

        $this->asOwner()->get('/admin/owner/month-closings/preview?period='.$lastMonth->format('Y-m'))
            ->assertOk()->assertSee('فواتير دلالين في الشهر لم تُقبل بعد: 1 قيد المراجعة');

        DalalInvoiceReview::where('sale_id', $sale->id)->update(['status' => DalalInvoiceReview::ACCEPTED]);
        $this->asOwner()->get('/admin/owner/month-closings/preview?period='.$lastMonth->format('Y-m'))
            ->assertOk()->assertDontSee('فواتير دلالين في الشهر');
    }
}
