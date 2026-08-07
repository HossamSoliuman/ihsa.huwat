<?php

namespace Tests\Feature;

use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\FishMarketUnit;
use App\Models\FishMarketWorker;
use App\Models\Governorate;
use App\Models\MarketJobTitle;
use App\Models\Nationality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FishMarketManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_and_unauthorised_roles_cannot_reach_the_markets_desk(): void
    {
        $this->get(route('information.admin.markets.index'))->assertRedirect(route('login'));

        $unauthorised = $this->userWithRole('hr_manager');

        $this->actingAs($unauthorised)->get(route('information.admin.markets.index'))->assertForbidden();
        $this->actingAs($unauthorised)->get(route('information.admin.markets.create'))->assertForbidden();
        $this->actingAs($unauthorised)->post(route('information.admin.markets.store'), [])->assertForbidden();
    }

    public function test_the_list_shows_each_market_with_its_region_and_derived_counts(): void
    {
        $market = FishMarket::factory()->create(['name' => 'سوق السمك المركزي بجدة']);
        FishMarketUnit::factory()->count(2)->shop()->create(['fish_market_id' => $market->id]);
        $stall = FishMarketUnit::factory()->auctionStall()->create(['fish_market_id' => $market->id]);
        FishMarketWorker::factory()->count(3)->create(['fish_market_unit_id' => $stall->id]);
        FishMarketBroker::factory()->create(['fish_market_id' => $market->id]);

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.markets.index'))
            ->assertOk()
            ->assertSee('أسواق السمك')
            ->assertSee('سوق السمك المركزي بجدة')
            ->assertSee($market->governorate->region->name)
            ->assertSee($market->governorate->name)
            ->assertViewHas('markets', fn ($markets): bool => $markets->first()->shops_count === 2
                && $markets->first()->auction_stalls_count === 1
                && $markets->first()->workers_count === 3
                && $markets->first()->brokers_count === 1);
    }

    public function test_the_list_filters_by_region_and_search_term(): void
    {
        $match = FishMarket::factory()->create(['name' => 'سوق القطيف', 'investor_name' => 'مؤسسة الخليج للأسماك']);
        $other = FishMarket::factory()->create(['name' => 'سوق ينبع']);

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.markets.index', [
                'region_id' => $match->governorate->region_id,
                'q' => 'الخليج',
            ]))
            ->assertOk()
            ->assertSee('سوق القطيف')
            ->assertDontSee('سوق ينبع');

        $this->assertNotSame($match->governorate->region_id, $other->governorate->region_id);
    }

    public function test_the_create_screen_chains_the_region_in_front_of_the_governorate(): void
    {
        $governorate = Governorate::factory()->create();

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.markets.create'))
            ->assertOk()
            ->assertSee('سوق سمك جديد')
            ->assertSee('معلومات المستثمر')
            ->assertSee($governorate->name)
            ->assertSee($governorate->region->name)
            /** The same client-side filter the ports desk uses, so no new script is needed. */
            ->assertSee('data-lookup-region-filter', false);
    }

    public function test_the_sidebar_nests_the_statuses_and_keeps_the_settings_entry_last(): void
    {
        $this->actingAs($this->supervisor())
            ->get(route('information.admin.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'لوحة التحكم',
                'الصيادين والبحارة',
                'كل الطلبات',
                'المرفوضة',
                'أسواق السمك',
                'الدلالين',
                'الإعدادات',
            ])
            /** Expanded on the submissions screen, so the active status is always visible. */
            ->assertSee('<details class="info-admin-nav-group" open>', false);
    }

    public function test_a_market_is_created_with_its_investor_in_one_post(): void
    {
        $governorate = Governorate::factory()->create();

        $this->actingAs($this->supervisor())
            ->post(route('information.admin.markets.store'), [
                'governorate_id' => $governorate->id,
                'name' => 'سوق السمك المركزي بجدة',
                'investor_name' => 'مؤسسة البحر الأحمر للأسماك',
                /** Arabic-Indic digits are what an Arabic keyboard actually produces. */
                'investor_commercial_registration_no' => '١٠١٠٢٣٤٥٦٧',
                'investor_phone' => '٠٥٠٠٠٠٠٠٠١',
                'investor_email' => 'investor@example.com',
                'investor_tax_number' => '300000000000003',
                'investor_national_address' => 'حي الميناء — جدة',
                'is_active' => '1',
            ])
            ->assertValid();

        $market = FishMarket::query()->sole();

        $this->assertSame('سوق السمك المركزي بجدة', $market->name);
        $this->assertSame('1010234567', $market->investor_commercial_registration_no);
        $this->assertSame('0500000001', $market->investor_phone);
        $this->assertTrue($market->is_active);
        /** المنطقة is read through the governorate rather than stored on the market. */
        $this->assertFalse(array_key_exists('region_id', $market->getAttributes()));
    }

    public function test_a_market_name_may_repeat_across_governorates_but_not_within_one(): void
    {
        $supervisor = $this->supervisor();
        $existing = FishMarket::factory()->create(['name' => 'السوق المركزي']);

        $this->actingAs($supervisor)
            ->post(route('information.admin.markets.store'), $this->marketPayload($existing->governorate_id, 'السوق المركزي'))
            ->assertInvalid(['name']);

        $this->actingAs($supervisor)
            ->post(route('information.admin.markets.store'), $this->marketPayload(Governorate::factory()->create()->id, 'السوق المركزي'))
            ->assertValid();

        $this->assertSame(2, FishMarket::query()->count());
    }

    public function test_a_market_without_investor_registration_details_is_refused(): void
    {
        $this->actingAs($this->supervisor())
            ->post(route('information.admin.markets.store'), [
                'governorate_id' => Governorate::factory()->create()->id,
                'name' => 'سوق بلا مستثمر',
                'investor_email' => 'not-an-email',
            ])
            ->assertInvalid(['investor_name', 'investor_commercial_registration_no', 'investor_email']);

        $this->assertSame(0, FishMarket::query()->count());
    }

    public function test_a_market_can_be_renamed_and_switched_off(): void
    {
        $market = FishMarket::factory()->create();

        $this->actingAs($this->supervisor())
            ->patch(route('information.admin.markets.update', $market), [
                ...$this->marketPayload($market->governorate_id, 'سوق السمك الجديد'),
                'is_active' => null,
            ])
            ->assertRedirect(route('information.admin.markets.show', $market));

        $market->refresh();
        $this->assertSame('سوق السمك الجديد', $market->name);
        $this->assertFalse($market->is_active);
    }

    public function test_shops_and_auction_stalls_are_added_from_the_market_page(): void
    {
        $market = FishMarket::factory()->create();
        $supervisor = $this->supervisor();

        foreach (FishMarketUnit::TYPES as $type) {
            $this->actingAs($supervisor)
                ->post(route('information.admin.markets.units.store', $market), [
                    'unit_type' => $type,
                    'label' => 'وحدة '.$type,
                    'entity_name' => 'مؤسسة '.$type,
                    'commercial_registration_no' => '1010'.random_int(100000, 999999),
                    'municipality_licence_no' => 'BL-1234',
                    'national_address' => 'حي الميناء',
                    'tax_number' => '300000000000003',
                    'is_active' => '1',
                ])
                ->assertValid();
        }

        $this->assertSame(1, $market->shops()->count());
        $this->assertSame(1, $market->auctionStalls()->count());

        $this->actingAs($supervisor)
            ->get(route('information.admin.markets.show', $market))
            ->assertOk()
            ->assertSee('محلات بيع السمك')
            ->assertSee('دكات الحراجات');
    }

    public function test_a_unit_belonging_to_another_market_is_not_addressable(): void
    {
        $market = FishMarket::factory()->create();
        $foreignUnit = FishMarketUnit::factory()->create();

        $this->actingAs($this->supervisor())
            ->delete(route('information.admin.markets.units.destroy', [$market, $foreignUnit]))
            ->assertNotFound();

        $this->assertModelExists($foreignUnit);
    }

    public function test_a_worker_is_registered_on_a_unit_with_codes_not_arabic_names(): void
    {
        $unit = FishMarketUnit::factory()->create();
        $nationality = array_key_first(Nationality::options());
        $jobTitle = array_key_first(MarketJobTitle::options());

        $this->actingAs($this->supervisor())
            ->post(route('information.admin.markets.units.workers.store', [$unit->fish_market_id, $unit]), [
                'full_name' => 'سالم علي الصياد',
                'national_id' => '١٠٢٣٤٥٦٧٨٩',
                'phone' => '0500000002',
                'email' => 'worker@example.com',
                'nationality' => $nationality,
                'job_title' => $jobTitle,
            ])
            ->assertValid();

        $worker = FishMarketWorker::query()->sole();

        $this->assertSame('1023456789', $worker->national_id);
        $this->assertSame($nationality, $worker->nationality);
        $this->assertSame($jobTitle, $worker->job_title);
        $this->assertArrayNotHasKey('national_id', $worker->toArray());
    }

    public function test_a_worker_identity_is_unique_within_a_unit_but_may_repeat_elsewhere(): void
    {
        $supervisor = $this->supervisor();
        $unit = FishMarketUnit::factory()->create();
        $otherUnit = FishMarketUnit::factory()->create(['fish_market_id' => $unit->fish_market_id]);
        FishMarketWorker::factory()->create(['fish_market_unit_id' => $unit->id, 'national_id' => '1023456789']);

        $this->actingAs($supervisor)
            ->post(route('information.admin.markets.units.workers.store', [$unit->fish_market_id, $unit]), $this->workerPayload('1023456789'))
            ->assertInvalid(['national_id']);

        $this->actingAs($supervisor)
            ->post(route('information.admin.markets.units.workers.store', [$otherUnit->fish_market_id, $otherUnit]), $this->workerPayload('1023456789'))
            ->assertValid();

        $this->assertSame(2, FishMarketWorker::query()->where('national_id', '1023456789')->count());
    }

    public function test_a_worker_identity_outside_the_national_format_is_refused(): void
    {
        $unit = FishMarketUnit::factory()->create();

        $this->actingAs($this->supervisor())
            ->post(route('information.admin.markets.units.workers.store', [$unit->fish_market_id, $unit]), [
                ...$this->workerPayload('3023456789'),
                'nationality' => 'not-a-code',
                'job_title' => 'not-a-code',
            ])
            ->assertInvalid(['national_id', 'nationality', 'job_title']);

        $this->assertSame(0, FishMarketWorker::query()->count());
    }

    public function test_a_worker_of_another_unit_is_not_addressable(): void
    {
        $unit = FishMarketUnit::factory()->create();
        $foreignWorker = FishMarketWorker::factory()->create();

        $this->actingAs($this->supervisor())
            ->delete(route('information.admin.markets.units.workers.destroy', [$unit->fish_market_id, $unit, $foreignWorker]))
            ->assertNotFound();

        $this->assertModelExists($foreignWorker);
    }

    public function test_a_unit_with_registered_workers_cannot_be_deleted(): void
    {
        $supervisor = $this->supervisor();
        $unit = FishMarketUnit::factory()->create();
        $worker = FishMarketWorker::factory()->create(['fish_market_unit_id' => $unit->id]);

        $this->actingAs($supervisor)
            ->delete(route('information.admin.markets.units.destroy', [$unit->fish_market_id, $unit]))
            ->assertInvalid(['delete']);

        $this->assertModelExists($unit);

        $this->actingAs($supervisor)
            ->delete(route('information.admin.markets.units.workers.destroy', [$unit->fish_market_id, $unit, $worker]))
            ->assertRedirect();

        $this->actingAs($supervisor)
            ->delete(route('information.admin.markets.units.destroy', [$unit->fish_market_id, $unit]))
            ->assertRedirect();

        $this->assertModelMissing($unit);
    }

    public function test_a_market_holding_units_or_brokers_cannot_be_deleted(): void
    {
        $supervisor = $this->supervisor();
        $market = FishMarket::factory()->create();
        $unit = FishMarketUnit::factory()->create(['fish_market_id' => $market->id]);
        $broker = FishMarketBroker::factory()->create(['fish_market_id' => $market->id]);

        $this->actingAs($supervisor)
            ->delete(route('information.admin.markets.destroy', $market))
            ->assertInvalid(['delete']);

        $unit->delete();

        $this->actingAs($supervisor)
            ->delete(route('information.admin.markets.destroy', $market))
            ->assertInvalid(['delete']);

        $broker->delete();

        $this->actingAs($supervisor)
            ->delete(route('information.admin.markets.destroy', $market))
            ->assertRedirect(route('information.admin.markets.index'));

        $this->assertModelMissing($market);
    }

    public function test_the_market_job_titles_list_is_maintained_from_the_reference_desk(): void
    {
        $supervisor = $this->supervisor();

        $this->actingAs($supervisor)
            ->get(route('information.admin.lookups.index', ['tab' => 'markets']))
            ->assertOk()
            ->assertSee('الوظائف في الأسواق')
            ->assertSee('بائع');

        $this->actingAs($supervisor)
            ->post(route('information.admin.lookups.options.store', 'market_job_titles'), ['name' => 'مسؤول ميزان'])
            ->assertRedirect(route('information.admin.lookups.index', ['tab' => 'markets']));

        $this->assertContains('مسؤول ميزان', MarketJobTitle::options());

        $unit = FishMarketUnit::factory()->create();

        $this->actingAs($supervisor)
            ->get(route('information.admin.markets.show', $unit->fish_market_id))
            ->assertOk()
            ->assertSee('مسؤول ميزان');
    }

    /** @return array<string, mixed> */
    private function marketPayload(int $governorateId, string $name): array
    {
        return [
            'governorate_id' => $governorateId,
            'name' => $name,
            'investor_name' => 'مؤسسة البحر الأحمر للأسماك',
            'investor_commercial_registration_no' => '1010'.random_int(100000, 999999),
            'is_active' => '1',
        ];
    }

    /** @return array<string, mixed> */
    private function workerPayload(string $nationalId): array
    {
        return [
            'full_name' => 'ناصر حسن البحار',
            'national_id' => $nationalId,
            'phone' => '0500000003',
            'nationality' => array_key_first(Nationality::options()),
            'job_title' => array_key_first(MarketJobTitle::options()),
        ];
    }

    private function supervisor(): User
    {
        return $this->userWithRole('quality_supervisor');
    }

    private function userWithRole(string $code): User
    {
        $role = Role::query()->where('code', $code)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
