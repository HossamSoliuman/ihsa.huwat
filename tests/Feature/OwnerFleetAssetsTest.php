<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Boat;
use App\Models\BoatInspection;
use App\Models\DocumentType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Fisher;
use App\Models\FishingEquipment;
use App\Models\FishingSeason;
use App\Models\FleetDocument;
use App\Models\GearType;
use App\Models\Port;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Owner\AssetDepreciation;
use App\Services\Owner\ExpenseService;
use Carbon\CarbonImmutable;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * O2 — أصول الأسطول: المعدات (ومواسمها وترحيل شرائها)، الأصول وإهلاكها
 * الشهري، الفحوصات وموعد القارب القادم، الوثائق وامتثال الطاقم، وتنبيهات الرئيسة.
 */
class OwnerFleetAssetsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Boat $boat;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        $this->owner = User::factory()->owner()->create(['name' => 'مالك الأسطول']);
        $port = Port::factory()->create(['name' => 'ميناء الأسطول (اختبار)']);
        $this->boat = Boat::factory()->ownedBy($this->owner)->create(['port_id' => $port->id, 'name' => 'قارب الأسطول', 'next_inspection_date' => null, 'license_expiry' => now()->addYears(2)]);
    }

    private function asOwner(): static
    {
        return $this->actingAs($this->owner);
    }

    private function asset(array $attributes = []): Asset
    {
        return Asset::factory()->ownedBy($this->owner)->create($attributes + [
            'asset_type_id' => AssetType::named('محرك')->id,
            'purchase_date' => '2026-01-15',
            'purchase_cost' => 12000,
            'salvage_value' => 0,
            'useful_life_years' => 5,
        ]);
    }

    public function test_pages_render_for_the_owner_only_and_sit_in_the_sidebar(): void
    {
        $this->asset();

        foreach (['equipment', 'assets', 'assets/depreciation', 'assets/print', 'assets/depreciation/print', 'inspections', 'documents'] as $page) {
            $this->asOwner()->get("/admin/owner/{$page}")->assertOk();
        }

        $this->asOwner()->get('/admin')
            ->assertSee(route('panel.owner.equipment'), false)
            ->assertSee(route('panel.owner.assets'), false)
            ->assertSee(route('panel.owner.inspections'), false)
            ->assertSee(route('panel.owner.documents'), false);

        $this->actingAs(User::factory()->dalal()->create())->get('/admin/owner/assets')->assertForbidden();
    }

    // ── المعدات ─────────────────────────────────────────────

    public function test_equipment_purchase_posts_an_expense_that_follows_it(): void
    {
        $vendor = Vendor::factory()->create(['owner_id' => $this->owner->id]);
        $gear = GearType::create(['name' => 'شباك خيشومية (اختبار)']);
        $season = FishingSeason::create(['name' => 'موسم الروبيان (اختبار)', 'species' => 'روبيان', 'start_month' => now()->month, 'end_month' => now()->month]);

        $this->asOwner()->post('/admin/owner/equipment', [
            'name' => 'شبكة 40 م',
            'boat_id' => $this->boat->id,
            'gear_type_id' => $gear->id,
            'vendor_id' => $vendor->id,
            'quantity' => 4,
            'unit_cost' => 250,
            'purchase_date' => '2026-09-01',
            'condition' => 'جيدة',
            'season_ids' => [$season->id],
        ])->assertRedirect(route('panel.owner.equipment'))
            ->assertSessionHas('status', fn ($s) => str_contains($s, 'EXP-'));

        $equipment = FishingEquipment::sole();
        $this->assertTrue($equipment->seasons->contains($season));
        $this->assertTrue($equipment->in_season);

        $expense = Expense::sole();
        $this->assertTrue($expense->source->is($equipment));
        $this->assertEquals(1000, $expense->total);
        $this->assertSame(ExpenseCategory::FISHING_EQUIPMENT, $expense->category->name);
        $this->assertSame($vendor->id, $expense->vendor_id);
        $this->assertSame($this->boat->id, $expense->boat_id);
        $this->assertSame('2026-09-01', $expense->date->toDateString());
        $this->asOwner()->get('/admin/owner/equipment')->assertSee($expense->expense_number)->assertSee('موسم الروبيان (اختبار)');
        $this->asOwner()->get('/admin/owner/expenses')->assertSee('معدات الصيد');

        // السند لا يُحذف ما دامت المعدات تُرحِّله.
        $this->asOwner()->from('/admin/owner/expenses')->delete("/admin/owner/expenses/{$expense->id}")->assertSessionHasErrors('expense');

        // تعديل الكمية يحدّث السند نفسه، والتكلفة الصفرية تلغيه (غير مدفوع).
        $this->asOwner()->put("/admin/owner/equipment/{$equipment->id}", ['name' => 'شبكة 40 م', 'quantity' => 6, 'unit_cost' => 250]);
        $this->assertEquals(1500, Expense::sole()->total);
        $this->assertCount(0, $equipment->fresh()->seasons);

        $this->asOwner()->put("/admin/owner/equipment/{$equipment->id}", ['name' => 'شبكة 40 م', 'quantity' => 6, 'unit_cost' => 0]);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_deleting_equipment_keeps_a_paid_expense_as_a_manual_one(): void
    {
        $equipment = app(\App\Services\Owner\FleetService::class)->saveEquipment($this->owner, ['name' => 'قراقير', 'quantity' => 10, 'unit_cost' => 30]);
        $expense = Expense::sole();
        app(ExpenseService::class)->recordPayment($this->owner, $expense, 300);

        $this->asOwner()->delete("/admin/owner/equipment/{$equipment->id}")->assertRedirect(route('panel.owner.equipment'));

        $this->assertDatabaseMissing('fishing_equipment', ['id' => $equipment->id]);
        $this->assertNull($expense->fresh()->source_type);
        $this->assertEquals(300, $expense->fresh()->paid_amount);
    }

    public function test_equipment_refuses_another_owners_boat_and_vendor(): void
    {
        $other = User::factory()->owner()->create();
        $theirBoat = Boat::factory()->ownedBy($other)->create(['port_id' => $this->boat->port_id]);
        $theirVendor = Vendor::factory()->create(['owner_id' => $other->id]);
        $theirs = FishingEquipment::factory()->ownedBy($other)->create();

        $this->asOwner()->from('/admin/owner/equipment')->post('/admin/owner/equipment', [
            'name' => 'x', 'quantity' => 1, 'boat_id' => $theirBoat->id, 'vendor_id' => $theirVendor->id,
        ])->assertSessionHasErrors(['boat_id', 'vendor_id']);

        $this->asOwner()->get('/admin/owner/equipment')->assertDontSee($theirs->name);
        $this->asOwner()->put("/admin/owner/equipment/{$theirs->id}", ['name' => 'x', 'quantity' => 1])->assertNotFound();
        $this->asOwner()->delete("/admin/owner/equipment/{$theirs->id}")->assertNotFound();
    }

    // ── الإهلاك ─────────────────────────────────────────────

    public function test_straight_line_depreciation_runs_from_the_purchase_month_for_the_useful_life(): void
    {
        $asset = $this->asset();
        $dep = app(AssetDepreciation::class);

        // 12000 ÷ 5 ÷ 12 = 200 شهريًا من يناير 2026 حتى ديسمبر 2030.
        $this->assertEquals(200, $dep->monthly($asset));
        $this->assertEquals(0, $dep->chargeFor($asset, 2025, 12));
        $this->assertEquals(200, $dep->chargeFor($asset, 2026, 1));
        $this->assertEquals(200, $dep->chargeFor($asset, 2030, 12));
        $this->assertEquals(0, $dep->chargeFor($asset, 2031, 1));

        $position = $dep->position($asset, CarbonImmutable::create(2026, 6, 1));
        $this->assertSame(6, $position['months_charged']);
        $this->assertEquals(1200, $position['accumulated']);
        $this->assertEquals(10800, $position['book_value']);

        $this->assertEquals(12000, $dep->position($asset, CarbonImmutable::create(2040, 1, 1))['accumulated']);
    }

    public function test_the_last_month_absorbs_rounding_so_the_total_is_exact(): void
    {
        $asset = $this->asset(['purchase_cost' => 1000, 'salvage_value' => 0, 'useful_life_years' => 3]);
        $dep = app(AssetDepreciation::class);

        $sum = 0.0;
        for ($i = 0; $i < 36; $i++) {
            $month = CarbonImmutable::create(2026, 1, 1)->addMonths($i);
            $sum += $dep->chargeFor($asset, $month->year, $month->month);
        }

        $this->assertEquals(27.78, $dep->monthly($asset));
        $this->assertEquals(27.70, $dep->chargeFor($asset, 2028, 12));
        $this->assertEqualsWithDelta(1000, $sum, 0.001);
    }

    public function test_a_disposed_asset_stops_after_its_disposal_month_and_shows_its_gain(): void
    {
        $asset = $this->asset(['status' => Asset::SOLD, 'disposed_at' => '2026-06-10', 'disposal_value' => 11500]);
        $dep = app(AssetDepreciation::class);

        $this->assertEquals(200, $dep->chargeFor($asset, 2026, 6));
        $this->assertEquals(0, $dep->chargeFor($asset, 2026, 7));

        $position = $dep->position($asset, CarbonImmutable::create(2027, 1, 1));
        $this->assertSame(6, $position['months_charged']);
        $this->assertSame(0, $position['remaining_months']);

        $row = $dep->register(collect([$asset]))['rows']->first();
        $this->assertEquals(10800, $row['book_value']);
        $this->assertEquals(700, $row['disposal_gain']);
    }

    public function test_year_schedule_and_month_total_filter_by_boat(): void
    {
        $this->asset(['boat_id' => $this->boat->id]);
        $this->asset(['name' => 'سيارة نقل', 'asset_type_id' => AssetType::named('مركبة')->id, 'purchase_date' => '2026-07-01', 'purchase_cost' => 6000, 'useful_life_years' => 5]);
        $dep = app(AssetDepreciation::class);

        $year = $dep->forYear($this->owner, 2026);
        $this->assertEquals(200, $year['months'][1]['total']);
        $this->assertEquals(300, $year['months'][7]['total']);
        $this->assertEquals(200 * 12 + 100 * 6, $year['year_total']);
        $this->assertEquals($year['year_total'], $year['months'][12]['accumulated']);

        $this->assertEquals(300, $dep->forMonth($this->owner, 2026, 9)['total']);
        $this->assertEquals(200, $dep->forMonth($this->owner, 2026, 9, $this->boat->id)['total']);

        $this->asOwner()->get('/admin/owner/assets/depreciation?year=2026')->assertOk()->assertSee('3,000.00')->assertSee('سيارة نقل');
    }

    public function test_asset_form_rules_for_disposal_and_salvage(): void
    {
        $base = [
            'asset_type_id' => AssetType::named('محرك')->id,
            'name' => 'محرك رئيسي',
            'purchase_date' => '2026-01-01',
            'purchase_cost' => 5000,
            'useful_life_years' => 5,
        ];

        $this->asOwner()->from('/admin/owner/assets')->post('/admin/owner/assets', $base + ['status' => Asset::SOLD])->assertSessionHasErrors('disposed_at');
        $this->asOwner()->from('/admin/owner/assets')->post('/admin/owner/assets', $base + ['status' => Asset::ACTIVE, 'salvage_value' => 6000])->assertSessionHasErrors('salvage_value');

        $this->asOwner()->post('/admin/owner/assets', $base + ['status' => Asset::ACTIVE, 'disposed_at' => '2026-05-01', 'disposal_value' => 10])->assertSessionHasNoErrors();
        $asset = Asset::sole();
        $this->assertNull($asset->disposed_at);
        $this->assertNull($asset->disposal_value);

        $this->asOwner()->put("/admin/owner/assets/{$asset->id}", $base + ['status' => Asset::DAMAGED, 'disposed_at' => '2026-05-01'])->assertSessionHasNoErrors();
        $this->assertSame('2026-05-01', $asset->fresh()->disposed_at->toDateString());

        $other = Asset::factory()->create();
        $this->asOwner()->delete("/admin/owner/assets/{$other->id}")->assertNotFound();
    }

    // ── الفحوصات ────────────────────────────────────────────

    public function test_the_latest_inspection_sets_the_boats_next_inspection_date(): void
    {
        $this->asOwner()->post('/admin/owner/inspections', [
            'boat_id' => $this->boat->id, 'inspection_date' => '2025-03-01', 'result' => 'مطابق',
        ])->assertRedirect(route('panel.owner.inspections'));

        // بلا موعد: بعد سنة إلا عشرة أيام.
        $this->assertSame('2026-02-19', $this->boat->fresh()->next_inspection_date->toDateString());

        $this->asOwner()->post('/admin/owner/inspections', [
            'boat_id' => $this->boat->id, 'inspection_date' => '2026-02-15', 'next_due_date' => '2026-08-15', 'result' => 'مطابق بملاحظات',
        ]);
        $this->assertSame('2026-08-15', $this->boat->fresh()->next_inspection_date->toDateString());

        $latest = BoatInspection::whereDate('inspection_date', '2026-02-15')->sole();
        $this->asOwner()->delete("/admin/owner/inspections/{$latest->id}");
        $this->assertSame('2026-02-19', $this->boat->fresh()->next_inspection_date->toDateString());

        $other = BoatInspection::factory()->create();
        $this->asOwner()->delete("/admin/owner/inspections/{$other->id}")->assertNotFound();
        $this->asOwner()->from('/admin/owner/inspections')->post('/admin/owner/inspections', [
            'boat_id' => $other->boat_id, 'inspection_date' => '2026-01-01', 'result' => 'مطابق',
        ])->assertSessionHasErrors('boat_id');
    }

    // ── الوثائق ─────────────────────────────────────────────

    public function test_documents_attach_to_boats_or_crew_and_drive_compliance(): void
    {
        Storage::fake('public');
        $crew = Fisher::factory()->ownedBy($this->owner)->create(['name' => 'بحّار الوثائق', 'port_id' => $this->boat->port_id]);
        $iqama = DocumentType::named('إقامة')->id;

        $this->asOwner()->post('/admin/owner/documents', [
            'holder_type' => 'crew', 'holder_id' => $crew->id, 'document_type_id' => $iqama,
            'number' => '2400000001', 'expiry_date' => now()->subDay()->toDateString(),
            'attachment' => UploadedFile::fake()->create('iqama.pdf', 50, 'application/pdf'),
        ])->assertSessionHasNoErrors();

        $doc = FleetDocument::sole();
        $this->assertTrue($doc->documentable->is($crew));
        $this->assertSame(FleetDocument::EXPIRED, $doc->status);
        Storage::disk('public')->assertExists($doc->attachment_path);

        $this->asOwner()->post('/admin/owner/documents', [
            'holder_type' => 'boat', 'holder_id' => $this->boat->id, 'document_type_id' => DocumentType::named('تأمين القارب')->id,
            'expiry_date' => now()->addDays(10)->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->asOwner()->get('/admin/owner/documents')
            ->assertSee('بحّار الوثائق')->assertSee('غير ممتثل')->assertSee('تنبيه');
        $this->asOwner()->get('/admin/owner/documents?status='.urlencode(FleetDocument::EXPIRED))
            ->assertSee('2400000001');
        $this->asOwner()->get('/admin/owner/documents?status='.urlencode(FleetDocument::EXPIRING))
            ->assertDontSee('2400000001');

        $path = $doc->attachment_path;
        $this->asOwner()->delete("/admin/owner/documents/{$doc->id}");
        Storage::disk('public')->assertMissing($path);

        // حائز لمالك آخر مرفوض.
        $theirCrew = Fisher::factory()->create(['owner_id' => User::factory()->owner()->create()->id, 'port_id' => $this->boat->port_id]);
        $this->asOwner()->from('/admin/owner/documents')->post('/admin/owner/documents', [
            'holder_type' => 'crew', 'holder_id' => $theirCrew->id, 'document_type_id' => $iqama,
        ])->assertSessionHasErrors('holder_id');
    }

    public function test_home_lists_fleet_alerts_nearest_first(): void
    {
        FleetDocument::factory()->create([
            'documentable_id' => $this->boat->id, 'owner_id' => $this->owner->id,
            'document_type_id' => DocumentType::named('استمارة القارب')->id,
            'expiry_date' => now()->subDays(3)->toDateString(),
        ]);
        FleetDocument::factory()->create([
            'documentable_id' => $this->boat->id, 'owner_id' => $this->owner->id,
            'document_type_id' => DocumentType::named('شهادة سلامة')->id,
            'expiry_date' => now()->addYear()->toDateString(),
        ]);
        $this->boat->update(['next_inspection_date' => now()->addDays(12)->toDateString()]);

        $this->asOwner()->get('/admin')
            ->assertOk()
            ->assertSee('تنبيهات الأسطول')
            ->assertSeeInOrder(['استمارة القارب', 'انتهى منذ 3 يوم', 'موعد الفحص', 'بعد 12 يوم'])
            ->assertDontSee('شهادة سلامة');
    }
}
