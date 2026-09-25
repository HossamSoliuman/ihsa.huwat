<?php

namespace Tests\Feature;

use App\Models\Boat;
use App\Models\BoatMaintenance;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseGroup;
use App\Models\PaymentStatus;
use App\Models\Port;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Owner\ExpenseService;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spectator\Spectator;
use Tests\TestCase;

/**
 * O1 — مصروفات المالك في /admin/owner/expenses: الخصم والضريبة وحالة الدفع
 * تحسبها الخدمة، والسندات مقيّدة بمالكها، والصيانة المكتملة تُرحَّل إليها.
 */
class OwnerExpensesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Boat $boat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        $this->owner = User::factory()->owner()->create(['name' => 'مالك المصروفات']);
        $port = Port::factory()->create(['name' => 'ميناء المصروفات (اختبار)']);
        $this->boat = Boat::factory()->ownedBy($this->owner)->create(['port_id' => $port->id, 'name' => 'قارب المصروفات']);
    }

    private function asOwner(?User $user = null): static
    {
        return $this->actingAs($user ?? $this->owner);
    }

    private function category(string $name = 'وقود'): int
    {
        return ExpenseCategory::named($name)->id;
    }

    private function paymentStatus(string $name): int
    {
        return PaymentStatus::named($name)->id;
    }

    private function makeExpense(array $attributes = [], ?User $owner = null): Expense
    {
        return app(ExpenseService::class)->create($owner ?? $this->owner, $attributes + [
            'expense_category_id' => $this->category(),
            'date' => '2026-09-20',
            'subtotal' => 1000,
        ]);
    }

    public function test_page_is_for_owners_only_and_sits_in_the_owner_sidebar(): void
    {
        $this->asOwner()->get('/admin/owner/expenses')
            ->assertOk()
            ->assertSee('recordFormEl')
            ->assertSee('وقود')
            ->assertSee(ExpenseGroup::MAINTENANCE);

        $this->asOwner()->get('/admin')->assertSee(route('panel.owner.expenses'), false);

        $this->actingAs(User::factory()->dalal()->create())->get('/admin/owner/expenses')->assertForbidden();
    }

    public function test_store_computes_discount_vat_and_payment_status(): void
    {
        $this->asOwner()->post('/admin/owner/expenses', [
            'expense_category_id' => $this->category(),
            'date' => '2026-09-20',
            'description' => 'وقود الرحلة',
            'subtotal' => 1000,
            'discount_pct' => 10,
            'vat_rate' => 15,
            'boat_id' => $this->boat->id,
            'payment_status_id' => $this->paymentStatus(ExpenseService::UNPAID),
        ])->assertRedirect(route('panel.owner.expenses'));

        $expense = Expense::sole();

        // 1000 − 10% = 900، + 15% = 135 → 1035.
        $this->assertSame('EXP-'.now()->year.'-0001', $expense->expense_number);
        $this->assertEquals(100, $expense->discount);
        $this->assertEquals(135, $expense->vat_amount);
        $this->assertEquals(1035, $expense->total);
        $this->assertEquals(0, $expense->paid_amount);
        $this->assertSame(ExpenseService::UNPAID, $expense->paymentStatus->name);
        $this->assertSame($this->owner->id, $expense->owner_id);

        $this->assertSame('EXP-'.now()->year.'-0002', $this->makeExpense()->expense_number);
    }

    public function test_a_fixed_discount_larger_than_the_amount_is_refused(): void
    {
        $this->asOwner()->from('/admin/owner/expenses')->post('/admin/owner/expenses', [
            'expense_category_id' => $this->category(),
            'date' => '2026-09-20',
            'subtotal' => 100,
            'discount' => 150,
        ])->assertSessionHasErrors('discount');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_paid_and_partial_statuses_set_the_paid_amount(): void
    {
        $paid = $this->makeExpense(['payment_status_id' => $this->paymentStatus(ExpenseService::PAID), 'vat_rate' => 15]);
        $this->assertEquals(1150, $paid->paid_amount);
        $this->assertTrue($paid->is_paid);

        $partial = $this->makeExpense(['payment_status_id' => $this->paymentStatus(ExpenseService::PARTIAL), 'paid_amount' => 400]);
        $this->assertEquals(400, $partial->paid_amount);
        $this->assertEquals(600, $partial->remaining);

        // جزئي بمبلغ يساوي الإجمالي ليس جزئيًا.
        $this->asOwner()->from('/admin/owner/expenses')->post('/admin/owner/expenses', [
            'expense_category_id' => $this->category(),
            'date' => '2026-09-20',
            'subtotal' => 100,
            'payment_status_id' => $this->paymentStatus(ExpenseService::PARTIAL),
            'paid_amount' => 100,
        ])->assertSessionHasErrors('paid_amount');
    }

    public function test_payments_accumulate_up_to_the_remaining_amount(): void
    {
        $expense = $this->makeExpense();

        $this->asOwner()->post("/admin/owner/expenses/{$expense->id}/payment", ['amount' => 300])->assertSessionHasNoErrors();
        $this->assertSame(ExpenseService::PARTIAL, $expense->fresh()->paymentStatus->name);

        $this->asOwner()->post("/admin/owner/expenses/{$expense->id}/payment", ['amount' => 800])->assertSessionHasErrors('amount');

        $this->asOwner()->post("/admin/owner/expenses/{$expense->id}/payment", ['amount' => 700])->assertSessionHasNoErrors();
        $expense->refresh();
        $this->assertEquals(1000, $expense->paid_amount);
        $this->assertSame(ExpenseService::PAID, $expense->paymentStatus->name);
    }

    public function test_a_trip_expense_is_booked_on_the_trips_boat(): void
    {
        $otherBoat = Boat::factory()->ownedBy($this->owner)->create(['port_id' => $this->boat->port_id]);
        $trip = Trip::factory()->onBoat($this->boat)->create();

        $expense = $this->makeExpense(['trip_id' => $trip->id, 'boat_id' => $otherBoat->id]);

        $this->assertSame($this->boat->id, $expense->boat_id);
        $this->asOwner()->get("/admin/owner/expenses?trip={$trip->id}")->assertSee($expense->expense_number);
    }

    public function test_another_owners_records_are_refused_and_hidden(): void
    {
        $other = User::factory()->owner()->create();
        $theirBoat = Boat::factory()->ownedBy($other)->create(['port_id' => $this->boat->port_id]);
        $theirTrip = Trip::factory()->onBoat($theirBoat)->create();
        $theirVendor = Vendor::factory()->create(['owner_id' => $other->id]);
        $theirs = $this->makeExpense(['description' => 'سند المالك الآخر'], $other);

        $this->asOwner()->from('/admin/owner/expenses')->post('/admin/owner/expenses', [
            'expense_category_id' => $this->category(),
            'date' => '2026-09-20',
            'subtotal' => 100,
            'boat_id' => $theirBoat->id,
            'trip_id' => $theirTrip->id,
            'vendor_id' => $theirVendor->id,
        ])->assertSessionHasErrors(['boat_id', 'trip_id', 'vendor_id']);

        $this->asOwner()->get('/admin/owner/expenses')->assertOk()->assertDontSee($theirs->expense_number);
        $this->asOwner()->put("/admin/owner/expenses/{$theirs->id}", ['expense_category_id' => $this->category(), 'date' => '2026-09-20', 'subtotal' => 1])->assertNotFound();
        $this->asOwner()->delete("/admin/owner/expenses/{$theirs->id}")->assertNotFound();
        $this->asOwner()->post("/admin/owner/expenses/{$theirs->id}/payment", ['amount' => 1])->assertNotFound();
        $this->asOwner()->get("/admin/owner/expenses/{$theirs->id}/print")->assertNotFound();
        $this->asOwner()->get('/admin/owner/expenses/report')->assertDontSee('سند المالك الآخر');
    }

    public function test_filters_totals_and_the_printed_report_follow_the_same_query(): void
    {
        $fuel = $this->makeExpense(['description' => 'وقود أيلول', 'date' => '2026-09-10', 'subtotal' => 500]);
        $fee = $this->makeExpense(['description' => 'رسوم أيلول', 'date' => '2026-09-12', 'subtotal' => 200, 'expense_category_id' => $this->category('رسوم تجديد')]);
        $old = $this->makeExpense(['description' => 'وقود آب', 'date' => '2026-08-01', 'subtotal' => 900]);

        $this->asOwner()->get('/admin/owner/expenses?from=2026-09-01')
            ->assertSee($fuel->expense_number)->assertSee($fee->expense_number)->assertDontSee($old->expense_number)
            ->assertSee('700.00');

        $government = ExpenseGroup::named('مصروفات حكومية')->id;
        $this->asOwner()->get("/admin/owner/expenses?group={$government}")
            ->assertSee($fee->expense_number)->assertDontSee($fuel->expense_number);

        $this->asOwner()->get('/admin/owner/expenses/report?from=2026-09-01&search=وقود')
            ->assertOk()
            ->assertSee('كشف المصروفات')
            ->assertSee('وقود أيلول')
            ->assertDontSee('رسوم أيلول')
            ->assertDontSee('وقود آب');

        $this->asOwner()->get("/admin/owner/expenses/{$fuel->id}/print")
            ->assertOk()->assertSee('سند صرف')->assertSee($fuel->expense_number)->assertSee('500.00');
    }

    public function test_update_recomputes_totals_and_keeps_the_paid_amount_within_them(): void
    {
        $expense = $this->makeExpense(['payment_status_id' => $this->paymentStatus(ExpenseService::PARTIAL), 'paid_amount' => 800]);

        $this->asOwner()->put("/admin/owner/expenses/{$expense->id}", [
            'expense_category_id' => $this->category('ثلج'),
            'date' => '2026-09-21',
            'subtotal' => 500,
        ])->assertSessionHasNoErrors();

        $expense->refresh();
        $this->assertEquals(500, $expense->total);
        $this->assertEquals(500, $expense->paid_amount);
        $this->assertSame(ExpenseService::PAID, $expense->paymentStatus->name);
        $this->assertSame('ثلج', $expense->category->name);
    }

    public function test_attachments_are_stored_replaced_and_removed(): void
    {
        Storage::fake('public');

        $this->asOwner()->post('/admin/owner/expenses', [
            'expense_category_id' => $this->category(),
            'date' => '2026-09-20',
            'subtotal' => 100,
            'attachment' => UploadedFile::fake()->create('receipt.pdf', 120, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $expense = Expense::sole();
        Storage::disk('public')->assertExists($expense->attachment_path);
        $first = $expense->attachment_path;

        $this->asOwner()->put("/admin/owner/expenses/{$expense->id}", [
            'expense_category_id' => $this->category(),
            'date' => '2026-09-20',
            'subtotal' => 100,
            'remove_attachment' => 1,
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($first);
        $this->assertNull($expense->fresh()->attachment_path);

        $this->asOwner()->from('/admin/owner/expenses')->post('/admin/owner/expenses', [
            'expense_category_id' => $this->category(),
            'date' => '2026-09-20',
            'subtotal' => 100,
            'attachment' => UploadedFile::fake()->create('virus.exe', 10),
        ])->assertSessionHasErrors('attachment');
    }

    public function test_completed_maintenance_posts_its_cost_as_an_expense(): void
    {
        $this->asOwner()->post('/admin/owner/maintenance', [
            'boat_id' => $this->boat->id,
            'date' => '2026-09-18',
            'estimated_cost' => 2500,
            'status' => 'معلقة',
        ])->assertRedirect(route('panel.owner.maintenance'));

        $maintenance = BoatMaintenance::sole();
        $this->assertDatabaseCount('expenses', 0);

        // الاكتمال يرحّل التكلفة الفعلية (لا المتوقعة).
        $this->asOwner()->put("/admin/owner/maintenance/{$maintenance->id}", [
            'boat_id' => $this->boat->id,
            'date' => '2026-09-18',
            'estimated_cost' => 2500,
            'actual_cost' => 2300,
            'status' => 'مكتملة',
        ])->assertSessionHas('status', fn ($s) => str_contains($s, 'رُحِّلت'));

        $expense = Expense::sole();
        $this->assertTrue($expense->source->is($maintenance));
        $this->assertEquals(2300, $expense->total);
        $this->assertSame($this->boat->id, $expense->boat_id);
        $this->assertSame(ExpenseCategory::BOAT_MAINTENANCE, $expense->category->name);
        $this->assertSame(ExpenseService::UNPAID, $expense->paymentStatus->name);
        $this->asOwner()->get('/admin/owner/maintenance')->assertSee($expense->expense_number);

        // تعديل التكلفة يحدّث السند نفسه.
        $this->asOwner()->put("/admin/owner/maintenance/{$maintenance->id}", [
            'boat_id' => $this->boat->id, 'date' => '2026-09-18', 'actual_cost' => 2000, 'status' => 'مكتملة',
        ]);
        $this->assertEquals(2000, Expense::sole()->total);

        // لا يُحذف السند من المصروفات ما دامت الصيانة مكتملة.
        $this->asOwner()->from('/admin/owner/expenses')->delete("/admin/owner/expenses/{$expense->id}")->assertSessionHasErrors('expense');

        // المبلغ والقارب يأتيان من الصيانة حتى لو أُرسلا في التعديل.
        $this->asOwner()->put("/admin/owner/expenses/{$expense->id}", [
            'expense_category_id' => $this->category('قطع غيار'),
            'date' => '2026-01-01',
            'subtotal' => 1,
            'vat_rate' => 15,
        ])->assertSessionHasNoErrors();
        $expense->refresh();
        $this->assertEquals(2300, $expense->total);
        $this->assertSame('2026-09-18', $expense->date->toDateString());
        $this->assertSame('قطع غيار', $expense->category->name);

        // الإرجاع عن الاكتمال يحذف السند غير المدفوع.
        $this->asOwner()->put("/admin/owner/maintenance/{$maintenance->id}", [
            'boat_id' => $this->boat->id, 'date' => '2026-09-18', 'actual_cost' => 2000, 'status' => 'معلقة',
        ]);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_a_paid_maintenance_expense_survives_the_maintenance(): void
    {
        $maintenance = BoatMaintenance::factory()->create(['boat_id' => $this->boat->id, 'estimated_cost' => 900, 'status' => 'مكتملة']);
        $expense = app(ExpenseService::class)->syncSource($maintenance, $this->owner);
        app(ExpenseService::class)->recordPayment($this->owner, $expense, 900);

        $this->asOwner()->put("/admin/owner/maintenance/{$maintenance->id}", [
            'boat_id' => $this->boat->id, 'date' => '2026-09-18', 'estimated_cost' => 900, 'status' => 'ملغاة',
        ]);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);

        $this->asOwner()->delete("/admin/owner/maintenance/{$maintenance->id}")->assertRedirect(route('panel.owner.maintenance'));

        $expense->refresh();
        $this->assertNull($expense->source_type);
        $this->assertEquals(900, $expense->paid_amount);

        $this->asOwner()->delete("/admin/owner/expenses/{$expense->id}")->assertSessionHasNoErrors();
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_maintenance_api_carries_the_actual_cost_and_its_expense(): void
    {
        Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');
        Sanctum::actingAs($this->owner);

        $response = $this->postJson('/api/v1/owner/maintenance', [
            'boat_id' => $this->boat->id,
            'date' => '2026-09-18',
            'estimated_cost' => 1500,
            'actual_cost' => 1400,
            'status' => 'مكتملة',
        ])->assertValidRequest()->assertValidResponse(201);

        $this->assertEquals(1400, $response->json('data.actual_cost'));
        $this->assertSame(Expense::sole()->expense_number, $response->json('data.expense.expense_number'));

        $this->getJson('/api/v1/owner/maintenance')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.0.expense.total', 1400);
    }
}
