<?php

namespace Tests\Feature;

use App\Models\BoatType;
use App\Models\City;
use App\Models\CrewRole;
use App\Models\FishingMethod;
use App\Models\Governorate;
use App\Models\HullMaterial;
use App\Models\InformationSubmission;
use App\Models\Nationality;
use App\Models\Port;
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
            ->assertSee('المدن')
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
        $governorate = Governorate::factory()->create();

        $this->actingAs($supervisor)
            ->post(route('information.admin.lookups.references.store', 'cities'), [
                'governorate_id' => $governorate->id,
                'name' => 'ثول',
            ])
            ->assertRedirect(route('information.admin.lookups.index', ['tab' => 'cities']));

        $this->assertDatabaseHas('cities', ['governorate_id' => $governorate->id, 'name' => 'ثول']);

        $this->actingAs($supervisor)
            ->post(route('information.admin.lookups.references.store', 'species'), ['name_ar' => 'شعري مرجاني'])
            ->assertRedirect(route('information.admin.lookups.index', ['tab' => 'species']));

        $this->assertDatabaseHas('fish_species', ['name_ar' => 'شعري مرجاني']);
    }

    public function test_a_port_is_switched_off_rather_than_lost(): void
    {
        $port = Port::factory()->create();

        $this->actingAs($this->supervisor())
            ->patch(route('information.admin.lookups.references.toggle', ['ports', $port->id]))
            ->assertRedirect(route('information.admin.lookups.index', ['tab' => 'ports']));

        $this->assertFalse($port->fresh()->is_active);
    }

    public function test_a_reference_record_already_used_by_a_submission_cannot_be_deleted(): void
    {
        $supervisor = $this->supervisor();
        $submission = InformationSubmission::factory()->create();
        $usedCity = City::factory()->create(['name' => $submission->owner_city]);
        /** Named outright so the faker cannot hand it the same city as the submission. */
        $unusedCity = City::factory()->create(['name' => 'مدينة بلا طلبات']);

        $this->actingAs($supervisor)
            ->delete(route('information.admin.lookups.references.destroy', ['cities', $usedCity->id]))
            ->assertInvalid(['delete']);

        $this->assertModelExists($usedCity);

        $this->actingAs($supervisor)
            ->delete(route('information.admin.lookups.references.destroy', ['cities', $unusedCity->id]))
            ->assertRedirect(route('information.admin.lookups.index', ['tab' => 'cities']));

        $this->assertModelMissing($unusedCity);
    }

    public function test_only_ports_expose_the_activation_toggle(): void
    {
        $city = City::factory()->create();

        $this->actingAs($this->supervisor())
            ->patch(route('information.admin.lookups.references.toggle', ['cities', $city->id]))
            ->assertNotFound();
    }

    public function test_the_city_field_offers_the_maintained_list_for_its_governorate(): void
    {
        $port = Port::factory()->create();
        City::factory()->create(['governorate_id' => $port->governorate_id, 'name' => 'ثول']);

        $this->confirmIdentity();

        $this->get(route('information.create'))->assertOk()->assertSee('ثول');
    }

    public function test_a_city_outside_the_maintained_list_is_refused(): void
    {
        $port = Port::factory()->create();
        City::factory()->create(['governorate_id' => $port->governorate_id, 'name' => 'ثول']);
        $this->confirmIdentity();

        $this->post(route('information.store'), [
            'owner_region' => $port->governorate->region->name,
            'owner_governorate' => $port->governorate->name,
            'owner_city' => 'مدينة غير مدرجة',
        ])->assertInvalid(['owner_city']);

        $this->post(route('information.store'), [
            'owner_region' => $port->governorate->region->name,
            'owner_governorate' => $port->governorate->name,
            'owner_city' => 'ثول',
        ])->assertValid(['owner_city']);
    }

    public function test_a_governorate_without_a_city_list_keeps_the_free_text_field(): void
    {
        $port = Port::factory()->create();
        $this->confirmIdentity();

        $this->post(route('information.store'), [
            'owner_region' => $port->governorate->region->name,
            'owner_governorate' => $port->governorate->name,
            'owner_city' => 'مدينة يكتبها المتقدم',
        ])->assertValid(['owner_city']);
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
