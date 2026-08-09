<?php

namespace Tests\Feature;

use App\Models\BoatType;
use App\Models\CrewRole;
use App\Models\FishingMethod;
use App\Models\FishSpecies;
use App\Models\Governorate;
use App\Models\HullMaterial;
use App\Models\InformationSubmission;
use App\Models\Nationality;
use App\Models\Port;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InformationLookupManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_and_unauthorised_roles_cannot_open_the_reference_desk(): void
    {
        $this->get(route('information.admin.lookups.index'))->assertRedirect(route('login'));

        $this->actingAs($this->userWithRole('hr_manager'))
            ->get(route('information.admin.lookups.index'))
            ->assertForbidden();
    }

    public function test_the_desk_groups_every_list_the_portal_form_draws_from(): void
    {
        $supervisor = $this->supervisor();

        $this->actingAs($supervisor)
            ->get(route('information.admin.lookups.index'))
            ->assertOk()
            ->assertSee('الإعدادات')
            ->assertSee('المناطق')
            ->assertSee('المحافظات')
            ->assertSee('أنواع الأسماك');

        $this->actingAs($supervisor)
            ->get(route('information.admin.lookups.index', ['tab' => 'boats']))
            ->assertOk()
            ->assertSee('أنواع القوارب')
            ->assertSee('قارب صغير')
            ->assertSee('مواد الهيكل')
            ->assertSee('ألياف زجاجية');

        $this->actingAs($supervisor)
            ->get(route('information.admin.lookups.index', ['tab' => 'fishing']))
            ->assertOk()
            ->assertSee('أساليب الصيد')
            ->assertSee('حالات أدوات الصيد');
    }

    public function test_an_option_added_to_a_list_reaches_the_portal_form(): void
    {
        $this->actingAs($this->supervisor())
            ->post(route('information.admin.lookups.options.store', 'hull_materials'), ['name' => 'ألياف كربونية'])
            ->assertRedirect(route('information.admin.lookups.index', ['tab' => 'boats']));

        $this->assertContains('ألياف كربونية', HullMaterial::options());

        $this->confirmIdentity();
        $this->get(route('information.create'))->assertOk()->assertSee('ألياف كربونية');
    }

    public function test_an_arabic_only_name_still_gets_a_stable_code_and_lands_last(): void
    {
        $this->actingAs($this->supervisor())
            ->post(route('information.admin.lookups.options.store', 'crew_roles'), ['name' => 'مساعد قبطان'])
            ->assertRedirect();

        $role = CrewRole::query()->where('name', 'مساعد قبطان')->sole();

        /** Submissions store the code, so it has to survive as latin text whatever the name reads. */
        $this->assertMatchesRegularExpression('/^[a-z0-9_]{2,60}$/', $role->code);
        $this->assertSame(70, $role->sort_order);
        $this->assertSame('مساعد قبطان', array_slice(CrewRole::options(), -1)[$role->code]);
    }

    public function test_a_name_already_on_the_list_is_refused(): void
    {
        $this->actingAs($this->supervisor())
            ->post(route('information.admin.lookups.options.store', 'nationalities'), ['name' => 'سعودي'])
            ->assertInvalid(['name']);

        $this->assertSame(9, Nationality::query()->count());
    }

    public function test_lists_are_separate_tables_so_a_name_may_repeat_across_them(): void
    {
        $this->assertContains('أخرى', HullMaterial::options());
        $this->assertContains('أخرى', CrewRole::options());

        $this->actingAs($this->supervisor())
            ->post(route('information.admin.lookups.options.store', 'fishing_methods'), ['name' => 'أخرى'])
            ->assertValid();

        $this->assertContains('أخرى', FishingMethod::options());
    }

    public function test_an_option_can_be_renamed_and_reordered(): void
    {
        $method = FishingMethod::query()->where('code', 'traps')->sole();

        $this->actingAs($this->supervisor())
            ->patch(route('information.admin.lookups.options.update', ['fishing_methods', $method]), [
                'name' => 'القراقير التقليدية',
                'sort_order' => 5,
            ])
            ->assertRedirect(route('information.admin.lookups.index', ['tab' => 'fishing']));

        $this->assertSame('القراقير التقليدية', $method->fresh()->name);
        $this->assertSame('traps', array_key_first(FishingMethod::options()));
    }

    public function test_retiring_an_option_hides_it_from_the_form_but_keeps_its_name_readable(): void
    {
        $boatType = BoatType::query()->where('code', 'recreational')->sole();

        $this->actingAs($this->supervisor())
            ->patch(route('information.admin.lookups.options.toggle', ['boat_types', $boatType]))
            ->assertRedirect(route('information.admin.lookups.index', ['tab' => 'boats']));

        $this->assertFalse($boatType->fresh()->is_active);
        $this->assertArrayNotHasKey('recreational', BoatType::options());
        $this->assertSame('قارب نزهة', BoatType::labels()['recreational']);
    }

    public function test_a_retired_option_is_refused_on_submission(): void
    {
        BoatType::query()->where('code', 'recreational')->sole()->update(['is_active' => false]);

        $this->confirmIdentity();

        $this->post(route('information.store'), ['boat_type' => 'recreational'])->assertInvalid(['boat_type']);
    }

    public function test_only_a_retired_option_can_be_deleted(): void
    {
        $supervisor = $this->supervisor();
        $role = CrewRole::query()->where('code', 'cook')->sole();

        $this->actingAs($supervisor)
            ->delete(route('information.admin.lookups.options.destroy', ['crew_roles', $role]))
            ->assertForbidden();

        $this->assertModelExists($role);

        $role->update(['is_active' => false]);

        $this->actingAs($supervisor)
            ->delete(route('information.admin.lookups.options.destroy', ['crew_roles', $role]))
            ->assertRedirect(route('information.admin.lookups.index', ['tab' => 'crew']));

        $this->assertModelMissing($role);
    }

    public function test_an_unknown_list_is_not_addressable(): void
    {
        $this->actingAs($this->supervisor())
            ->post(route('information.admin.lookups.index').'/lists/users', ['name' => 'دخيل'])
            ->assertNotFound();
    }

    public function test_reference_records_can_be_added_from_the_desk(): void
    {
        $supervisor = $this->supervisor();
        $region = Region::factory()->create();

        $this->actingAs($supervisor)
            ->post(route('information.admin.lookups.references.store', 'governorates'), [
                'region_id' => $region->id,
                'name' => 'خليص',
                'is_active' => '1',
            ])
            ->assertRedirect(route('information.admin.lookups.index', ['tab' => 'governorates']));

        $this->assertDatabaseHas('governorates', ['region_id' => $region->id, 'name' => 'خليص', 'is_active' => true]);

        $this->actingAs($supervisor)
            ->post(route('information.admin.lookups.references.store', 'species'), ['name_ar' => 'شعري مرجاني'])
            ->assertRedirect(route('information.admin.lookups.index', ['tab' => 'species']));

        $this->assertDatabaseHas('fish_species', ['name_ar' => 'شعري مرجاني']);
    }

    public function test_a_region_a_governorate_and_a_port_are_switched_off_rather_than_lost(): void
    {
        $supervisor = $this->supervisor();
        $port = Port::factory()->create();
        $governorate = $port->governorate;
        $region = $governorate->region;

        foreach (['regions' => $region, 'governorates' => $governorate, 'ports' => $port] as $type => $record) {
            $this->actingAs($supervisor)
                ->patch(route('information.admin.lookups.references.toggle', [$type, $record->id]))
                ->assertRedirect(route('information.admin.lookups.index', ['tab' => $type]));

            $this->assertFalse($record->fresh()->is_active);

            $this->actingAs($supervisor)
                ->get(route('information.admin.lookups.index', ['tab' => $type]))
                ->assertOk()
                ->assertSee('متوقف')
                ->assertSee('تفعيل');
        }
    }

    public function test_a_rejected_port_reopens_the_desk_with_the_region_picker_intact(): void
    {
        $port = Port::factory()->create(['name' => 'ميناء قائم']);
        $tab = route('information.admin.lookups.index', ['tab' => 'ports']);

        /** The region select carries no `name`, so it has no old input of its own to read back. */
        $this->actingAs($this->supervisor())
            ->from($tab)
            ->followingRedirects()
            ->post(route('information.admin.lookups.references.store', 'ports'), [
                'governorate_id' => $port->governorate_id,
                'name' => '',
            ])
            ->assertOk()
            ->assertSee($port->governorate->region->name)
            ->assertSee($port->governorate->name);
    }

    public function test_the_desk_lists_live_records_before_retired_ones(): void
    {
        Region::factory()->create(['name' => 'منطقة تعمل']);
        Region::factory()->create(['name' => 'منطقة متقاعدة', 'is_active' => false]);

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.lookups.index', ['tab' => 'regions']))
            ->assertOk()
            ->assertSeeInOrder(['منطقة تعمل', 'منطقة متقاعدة']);
    }

    public function test_a_record_stopped_with_the_geography_above_it_sorts_below_the_live_ones(): void
    {
        $liveRegion = Region::factory()->create(['name' => 'منطقة قائمة للترتيب']);
        $retiredRegion = Region::factory()->create(['name' => 'منطقة متقاعدة للترتيب', 'is_active' => false]);

        /** Named so the alphabet alone would invert the order: only the status sort can produce it. */
        $stoppedWithRegion = Governorate::factory()->create(['region_id' => $retiredRegion->id, 'name' => 'ألف تابعة']);
        $retiredGovernorate = Governorate::factory()->create(['region_id' => $liveRegion->id, 'name' => 'باء موقوفة', 'is_active' => false]);
        $liveGovernorate = Governorate::factory()->create(['region_id' => $liveRegion->id, 'name' => 'ياء قائمة']);

        $supervisor = $this->supervisor();

        $this->actingAs($supervisor)
            ->get(route('information.admin.lookups.index', ['tab' => 'governorates']))
            ->assertOk()
            ->assertSeeInOrder(['ياء قائمة', 'ألف تابعة', 'باء موقوفة']);

        Port::factory()->for($stoppedWithRegion, 'governorate')->create(['name' => 'ألف ميناء تابع']);
        Port::factory()->for($retiredGovernorate, 'governorate')->create(['name' => 'باء ميناء تابع لمحافظة موقوفة']);
        Port::factory()->for($liveGovernorate, 'governorate')->create(['name' => 'تاء ميناء موقوف', 'is_active' => false]);
        Port::factory()->for($liveGovernorate, 'governorate')->create(['name' => 'ياء ميناء قائم']);

        $this->actingAs($supervisor)
            ->get(route('information.admin.lookups.index', ['tab' => 'ports']))
            ->assertOk()
            ->assertSeeInOrder(['ياء ميناء قائم', 'ألف ميناء تابع', 'باء ميناء تابع لمحافظة موقوفة', 'تاء ميناء موقوف']);
    }

    public function test_a_reference_record_already_used_by_a_submission_cannot_be_deleted(): void
    {
        $supervisor = $this->supervisor();
        $submission = InformationSubmission::factory()->create();
        $usedGovernorate = Governorate::factory()->create(['name' => $submission->owner_governorate]);
        /** Named outright so the faker cannot hand it the same name as the submission. */
        $unusedGovernorate = Governorate::factory()->create(['name' => 'محافظة بلا طلبات']);

        $this->actingAs($supervisor)
            ->delete(route('information.admin.lookups.references.destroy', ['governorates', $usedGovernorate->id]))
            ->assertInvalid(['delete']);

        $this->assertModelExists($usedGovernorate);

        $this->actingAs($supervisor)
            ->delete(route('information.admin.lookups.references.destroy', ['governorates', $unusedGovernorate->id]))
            ->assertRedirect(route('information.admin.lookups.index', ['tab' => 'governorates']));

        $this->assertModelMissing($unusedGovernorate);
    }

    public function test_a_record_without_an_activation_flag_has_no_toggle(): void
    {
        $species = FishSpecies::query()->firstOrFail();

        $this->actingAs($this->supervisor())
            ->patch(route('information.admin.lookups.references.toggle', ['species', $species->id]))
            ->assertNotFound();
    }

    public function test_a_retired_region_and_governorate_leave_the_portal_form(): void
    {
        $retiredRegion = Region::factory()->create(['name' => 'منطقة موقوفة']);
        /** Retiring the region has to take its governorates off the form with it. */
        Governorate::factory()->create(['region_id' => $retiredRegion->id, 'name' => 'محافظة يتيمة']);

        $liveRegion = Region::factory()->create(['name' => 'منطقة قائمة']);
        $retiredGovernorate = Governorate::factory()->create(['region_id' => $liveRegion->id, 'name' => 'محافظة موقوفة']);
        Governorate::factory()->create(['region_id' => $liveRegion->id, 'name' => 'محافظة قائمة']);

        $this->confirmIdentity();

        $this->get(route('information.create'))->assertOk()->assertSee('منطقة موقوفة')->assertSee('محافظة موقوفة');

        $retiredRegion->update(['is_active' => false]);
        $retiredGovernorate->update(['is_active' => false]);

        $this->get(route('information.create'))
            ->assertOk()
            ->assertSee('منطقة قائمة')
            ->assertSee('محافظة قائمة')
            ->assertDontSee('منطقة موقوفة')
            ->assertDontSee('محافظة موقوفة')
            ->assertDontSee('محافظة يتيمة');
    }

    public function test_a_port_under_retired_geography_is_offered_no_more_than_its_region(): void
    {
        $port = Port::factory()->create(['name' => 'ميناء تابع']);
        $governorate = $port->governorate;
        $region = $governorate->region;
        $this->confirmIdentity();

        $this->get(route('information.create'))->assertOk()->assertSee('ميناء تابع');
        $this->post(route('information.store'), ['port_id' => $port->id])->assertValid(['port_id']);

        $region->update(['is_active' => false]);

        $this->get(route('information.create'))->assertOk()->assertDontSee('ميناء تابع');
        $this->post(route('information.store'), ['port_id' => $port->id])->assertInvalid(['port_id']);

        $region->update(['is_active' => true]);
        $governorate->update(['is_active' => false]);

        $this->get(route('information.create'))->assertOk()->assertDontSee('ميناء تابع');
        $this->post(route('information.store'), ['port_id' => $port->id])->assertInvalid(['port_id']);
    }

    public function test_a_retired_region_or_governorate_is_refused_on_submission(): void
    {
        /** Named outright: the factory draws real Saudi city names, which the seeded geography already carries. */
        $region = Region::factory()->create(['name' => 'منطقة الطلبات']);
        $governorate = Governorate::factory()->create(['region_id' => $region->id, 'name' => 'محافظة الطلبات']);
        $this->confirmIdentity();

        $payload = [
            'owner_region' => $region->name,
            'owner_governorate' => $governorate->name,
        ];

        $this->post(route('information.store'), $payload)->assertValid(['owner_region', 'owner_governorate']);

        $region->update(['is_active' => false]);
        $this->post(route('information.store'), $payload)->assertInvalid(['owner_region']);

        $region->update(['is_active' => true]);
        $governorate->update(['is_active' => false]);
        $this->post(route('information.store'), $payload)->assertInvalid(['owner_governorate']);
    }

    /** Walk the public gate so the portal form is reachable. */
    private function confirmIdentity(): void
    {
        $this->post(route('information.identity.store'), [
            'national_id' => '1023456789',
            'phone' => '0500000000',
        ])->assertRedirect(route('information.create'));
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
