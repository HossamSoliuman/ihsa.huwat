<?php

namespace Tests\Feature;

use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\FishMarketBrokerEmployee;
use App\Models\FishMarketUnit;
use App\Models\MarketJobTitle;
use App\Models\Nationality;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FishMarketBrokerManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_and_unauthorised_roles_cannot_reach_the_brokers_desk(): void
    {
        $this->get(route('information.admin.brokers.index'))->assertRedirect(route('login'));

        $unauthorised = $this->userWithRole('hr_manager');

        $this->actingAs($unauthorised)->get(route('information.admin.brokers.index'))->assertForbidden();
        $this->actingAs($unauthorised)->get(route('information.admin.brokers.create'))->assertForbidden();
        $this->actingAs($unauthorised)->post(route('information.admin.brokers.store'), [])->assertForbidden();
    }

    public function test_the_list_shows_both_branches_with_the_market_they_belong_to(): void
    {
        $market = FishMarket::factory()->create(['name' => 'سوق السمك المركزي بجدة']);
        $establishment = FishMarketBroker::factory()->create([
            'fish_market_id' => $market->id,
            'entity_name' => 'مؤسسة الدلالة الحديثة',
        ]);
        $individual = FishMarketBroker::factory()->individual()->create([
            'fish_market_id' => $market->id,
            'full_name' => 'سالم علي الدلال',
        ]);

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.brokers.index'))
            ->assertOk()
            ->assertSee('الدلالين')
            ->assertSee($establishment->entity_name)
            ->assertSee($individual->full_name)
            ->assertSee('منشأة')
            ->assertSee('فرد')
            ->assertSee('سوق السمك المركزي بجدة');
    }

    public function test_the_list_filters_by_market_and_entity_type(): void
    {
        $market = FishMarket::factory()->create();
        $individual = FishMarketBroker::factory()->individual()->create([
            'fish_market_id' => $market->id,
            'full_name' => 'سالم علي الدلال',
        ]);
        $elsewhere = FishMarketBroker::factory()->create(['entity_name' => 'مؤسسة سوق آخر']);

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.brokers.index', [
                'fish_market_id' => $market->id,
                'entity_type' => FishMarketBroker::TYPE_INDIVIDUAL,
            ]))
            ->assertOk()
            ->assertSee($individual->full_name)
            ->assertDontSee($elsewhere->entity_name);
    }

    public function test_the_create_screen_offers_both_branches_and_the_market_list(): void
    {
        $market = FishMarket::factory()->create(['name' => 'سوق السمك المركزي بجدة']);

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.brokers.create'))
            ->assertOk()
            ->assertSee('دلال جديد')
            ->assertSee('بيانات الفرد')
            ->assertSee('بيانات المنشأة')
            ->assertSee($market->name);
    }

    public function test_the_create_screen_says_a_market_has_to_exist_first(): void
    {
        $this->actingAs($this->supervisor())
            ->get(route('information.admin.brokers.create'))
            ->assertOk()
            ->assertSee('لا توجد أسواق مسجلة')
            ->assertDontSee('بيانات المنشأة');
    }

    public function test_an_individual_broker_keeps_only_the_fields_of_its_own_branch(): void
    {
        $market = FishMarket::factory()->create();

        $this->actingAs($this->supervisor())
            ->post(route('information.admin.brokers.store'), [
                'fish_market_id' => $market->id,
                'entity_type' => FishMarketBroker::TYPE_INDIVIDUAL,
                'full_name' => 'سالم علي الدلال',
                'nationality' => array_key_first(Nationality::options()),
                'job_title' => array_key_first(MarketJobTitle::options()),
                'phone' => '٠٥٠٠٠٠٠٠٠٤',
                /** Sent by the fieldset the form hides; the request must drop it. */
                'entity_name' => 'مؤسسة لا تنتمي لهذا السجل',
                'commercial_registration_no' => '1010999999',
                'email' => 'establishment@example.com',
                'is_active' => '1',
            ])
            ->assertValid();

        $broker = FishMarketBroker::query()->sole();

        $this->assertSame(FishMarketBroker::TYPE_INDIVIDUAL, $broker->entity_type);
        $this->assertSame('سالم علي الدلال', $broker->full_name);
        $this->assertSame('0500000004', $broker->phone);
        $this->assertNull($broker->entity_name);
        $this->assertNull($broker->commercial_registration_no);
        $this->assertNull($broker->email);
    }

    public function test_an_establishment_broker_keeps_only_the_fields_of_its_own_branch(): void
    {
        $market = FishMarket::factory()->create();

        $this->actingAs($this->supervisor())
            ->post(route('information.admin.brokers.store'), [
                ...$this->establishmentPayload($market->id),
                'full_name' => 'اسم فرد لا ينتمي لهذا السجل',
                'nationality' => array_key_first(Nationality::options()),
            ])
            ->assertValid();

        $broker = FishMarketBroker::query()->sole();

        $this->assertSame(FishMarketBroker::TYPE_ESTABLISHMENT, $broker->entity_type);
        $this->assertSame('مؤسسة الدلالة الحديثة', $broker->entity_name);
        $this->assertSame('1010234567', $broker->commercial_registration_no);
        $this->assertNull($broker->full_name);
        $this->assertNull($broker->nationality);
        $this->assertNull($broker->job_title);
    }

    public function test_each_branch_requires_its_own_fields(): void
    {
        $supervisor = $this->supervisor();
        $market = FishMarket::factory()->create();

        $this->actingAs($supervisor)
            ->post(route('information.admin.brokers.store'), [
                'fish_market_id' => $market->id,
                'entity_type' => FishMarketBroker::TYPE_INDIVIDUAL,
            ])
            ->assertInvalid(['full_name', 'nationality', 'job_title']);

        $this->actingAs($supervisor)
            ->post(route('information.admin.brokers.store'), [
                'fish_market_id' => $market->id,
                'entity_type' => FishMarketBroker::TYPE_ESTABLISHMENT,
            ])
            ->assertInvalid(['entity_name', 'commercial_registration_no', 'email', 'tax_number', 'national_address']);

        $this->assertSame(0, FishMarketBroker::query()->count());
    }

    public function test_a_broker_keeps_the_stall_it_works_out_of_and_its_counted_employees(): void
    {
        $market = FishMarket::factory()->create();
        $stall = FishMarketUnit::factory()->auctionStall()->create([
            'fish_market_id' => $market->id,
            'label' => 'دكة 3',
        ]);
        [$firstJobTitle, $secondJobTitle] = array_slice(array_keys(MarketJobTitle::options()), 0, 2);
        $nationality = array_key_first(Nationality::options());

        $this->actingAs($this->supervisor())
            ->post(route('information.admin.brokers.store'), [
                ...$this->establishmentPayload($market->id),
                'fish_market_unit_id' => $stall->id,
                'stall_number' => '١٤',
                'employees' => [
                    ['job_title' => $firstJobTitle, 'nationality' => $nationality, 'headcount' => '٣'],
                    ['job_title' => $secondJobTitle, 'nationality' => $nationality, 'headcount' => 2],
                    /** The blank row the form always offers is not an employee record. */
                    ['job_title' => '', 'nationality' => '', 'headcount' => ''],
                ],
            ])
            ->assertValid();

        $broker = FishMarketBroker::query()->sole();

        $this->assertSame($stall->id, $broker->fish_market_unit_id);
        $this->assertSame('14', $broker->stall_number);
        $this->assertSame(2, $broker->employees()->count());
        $this->assertSame(3, $broker->employees()->where('job_title', $firstJobTitle)->sole()->headcount);
    }

    public function test_the_stall_has_to_be_an_auction_stall_of_the_same_market(): void
    {
        $supervisor = $this->supervisor();
        $market = FishMarket::factory()->create();
        $elsewhere = FishMarketUnit::factory()->auctionStall()->create();
        $shop = FishMarketUnit::factory()->shop()->create(['fish_market_id' => $market->id]);

        $this->actingAs($supervisor)
            ->post(route('information.admin.brokers.store'), [
                ...$this->establishmentPayload($market->id),
                'fish_market_unit_id' => $elsewhere->id,
            ])
            ->assertInvalid(['fish_market_unit_id']);

        $this->actingAs($supervisor)
            ->post(route('information.admin.brokers.store'), [
                ...$this->establishmentPayload($market->id),
                'fish_market_unit_id' => $shop->id,
            ])
            ->assertInvalid(['fish_market_unit_id']);

        $this->assertSame(0, FishMarketBroker::query()->count());
    }

    public function test_an_employee_row_is_filed_whole_and_the_same_pair_is_not_counted_twice(): void
    {
        $supervisor = $this->supervisor();
        $market = FishMarket::factory()->create();
        $jobTitle = array_key_first(MarketJobTitle::options());
        $nationality = array_key_first(Nationality::options());

        $this->actingAs($supervisor)
            ->post(route('information.admin.brokers.store'), [
                ...$this->establishmentPayload($market->id),
                'employees' => [['job_title' => $jobTitle, 'nationality' => '', 'headcount' => '']],
            ])
            /** Spelled out because a `*` in a message key spans one segment, not a whole path. */
            ->assertInvalid([
                'employees.0.nationality' => 'هذا الحقل مطلوب.',
                'employees.0.headcount' => 'هذا الحقل مطلوب.',
            ]);

        $this->actingAs($supervisor)
            ->post(route('information.admin.brokers.store'), [
                ...$this->establishmentPayload($market->id),
                'employees' => [
                    ['job_title' => $jobTitle, 'nationality' => $nationality, 'headcount' => 3],
                    ['job_title' => $jobTitle, 'nationality' => $nationality, 'headcount' => 2],
                ],
            ])
            ->assertInvalid(['employees']);

        $this->assertSame(0, FishMarketBroker::query()->count());
    }

    public function test_editing_a_broker_replaces_the_employee_rows_it_was_filed_with(): void
    {
        $broker = FishMarketBroker::factory()->create();
        FishMarketBrokerEmployee::factory()->count(3)->create(['fish_market_broker_id' => $broker->id]);
        $jobTitle = array_key_first(MarketJobTitle::options());
        $nationality = array_key_first(Nationality::options());

        $this->actingAs($this->supervisor())
            ->patch(route('information.admin.brokers.update', $broker), [
                ...$this->establishmentPayload($broker->fish_market_id),
                'employees' => [['job_title' => $jobTitle, 'nationality' => $nationality, 'headcount' => 5]],
            ])
            ->assertRedirect(route('information.admin.brokers.show', $broker));

        $employee = $broker->employees()->sole();

        $this->assertSame($jobTitle, $employee->job_title);
        $this->assertSame(5, $employee->headcount);
    }

    public function test_the_broker_page_offers_the_stalls_and_the_employee_block(): void
    {
        $broker = FishMarketBroker::factory()->create();
        $stall = FishMarketUnit::factory()->auctionStall()->create([
            'fish_market_id' => $broker->fish_market_id,
            'label' => 'دكة 7',
        ]);

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.brokers.show', $broker))
            ->assertOk()
            ->assertSee('الدكة المنسوب إليها')
            ->assertSee('رقم الدكة')
            ->assertSee('الموظفون')
            ->assertSee($stall->label);
    }

    public function test_a_broker_must_be_attached_to_a_known_market_and_a_known_branch(): void
    {
        $this->actingAs($this->supervisor())
            ->post(route('information.admin.brokers.store'), [
                'fish_market_id' => 9999,
                'entity_type' => 'company',
            ])
            ->assertInvalid(['fish_market_id', 'entity_type']);
    }

    public function test_editing_a_broker_cannot_switch_the_branch_it_was_filed_under(): void
    {
        $broker = FishMarketBroker::factory()->individual()->create(['full_name' => 'سالم علي الدلال']);

        $this->actingAs($this->supervisor())
            ->patch(route('information.admin.brokers.update', $broker), [
                'fish_market_id' => $broker->fish_market_id,
                /** Ignored: the stored branch is merged over whatever the request carried. */
                'entity_type' => FishMarketBroker::TYPE_ESTABLISHMENT,
                'full_name' => 'سالم علي الدلال الكبير',
                'nationality' => array_key_first(Nationality::options()),
                'job_title' => array_key_first(MarketJobTitle::options()),
                'is_active' => '1',
            ])
            ->assertRedirect(route('information.admin.brokers.show', $broker));

        $broker->refresh();
        $this->assertSame(FishMarketBroker::TYPE_INDIVIDUAL, $broker->entity_type);
        $this->assertSame('سالم علي الدلال الكبير', $broker->full_name);
        $this->assertNull($broker->entity_name);
    }

    public function test_a_broker_can_be_switched_off_and_deleted(): void
    {
        $supervisor = $this->supervisor();
        $broker = FishMarketBroker::factory()->create();

        $this->actingAs($supervisor)
            ->patch(route('information.admin.brokers.update', $broker), [
                ...$this->establishmentPayload($broker->fish_market_id),
                'is_active' => null,
            ])
            ->assertRedirect(route('information.admin.brokers.show', $broker));

        $this->assertFalse($broker->refresh()->is_active);

        $this->actingAs($supervisor)
            ->delete(route('information.admin.brokers.destroy', $broker))
            ->assertRedirect(route('information.admin.brokers.index'));

        $this->assertModelMissing($broker);
    }

    public function test_the_broker_page_offers_only_the_branch_of_the_record(): void
    {
        $broker = FishMarketBroker::factory()->individual()->create();

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.brokers.show', $broker))
            ->assertOk()
            ->assertSee('بيانات الفرد')
            ->assertDontSee('بيانات المنشأة');
    }

    /** @return array<string, mixed> */
    private function establishmentPayload(int $marketId): array
    {
        return [
            'fish_market_id' => $marketId,
            'entity_type' => FishMarketBroker::TYPE_ESTABLISHMENT,
            'entity_name' => 'مؤسسة الدلالة الحديثة',
            'commercial_registration_no' => '١٠١٠٢٣٤٥٦٧',
            'email' => 'broker@example.com',
            'tax_number' => '300000000000003',
            'national_address' => 'حي الميناء — جدة',
            'phone' => '0500000005',
            'is_active' => '1',
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
