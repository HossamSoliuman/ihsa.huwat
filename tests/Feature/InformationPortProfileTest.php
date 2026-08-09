<?php

namespace Tests\Feature;

use App\Models\Boat;
use App\Models\Governorate;
use App\Models\HarborBoatCapacity;
use App\Models\HarborLicense;
use App\Models\HarborViolation;
use App\Models\HarborWorker;
use App\Models\InformationSubmission;
use App\Models\Port;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class InformationPortProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_and_unauthorised_roles_cannot_reach_the_ports_desk(): void
    {
        $port = Port::factory()->create();

        $this->get(route('information.admin.ports.index'))->assertRedirect(route('login'));

        $unauthorised = $this->userWithRole('hr_manager');

        $this->actingAs($unauthorised)->get(route('information.admin.ports.index'))->assertForbidden();
        $this->actingAs($unauthorised)->get(route('information.admin.ports.show', $port))->assertForbidden();
    }

    public function test_the_index_cards_only_carry_live_ports_with_their_derived_figures(): void
    {
        $port = $this->port('ميناء جدة الإسلامي');
        HarborBoatCapacity::factory()->create(['port_id' => $port->id, 'boat_type' => 'small', 'capacity' => 30]);
        HarborBoatCapacity::factory()->create(['port_id' => $port->id, 'boat_type' => 'large', 'capacity' => 10]);
        Boat::factory()->count(2)->create(['home_port_id' => $port->id, 'boat_type' => 'small', 'harbor_status' => 'occupied']);
        Boat::factory()->create(['home_port_id' => $port->id, 'boat_type' => 'small', 'harbor_status' => 'disabled']);
        HarborWorker::factory()->create(['port_id' => $port->id]);
        InformationSubmission::factory()->count(3)->create(['port_id' => $port->id]);

        $retired = $this->port('ميناء متوقف', ['is_active' => false]);

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.ports.index'))
            ->assertOk()
            ->assertSee('ميناء جدة الإسلامي')
            ->assertDontSee($retired->name)
            ->assertViewHas('ports', function ($ports) use ($port): bool {
                $card = $ports->firstWhere('id', $port->id);

                return (int) $card->berth_capacity === 40
                    && $card->occupied_boats_count === 2
                    && $card->workers_count === 1
                    && $card->submissions_count === 3;
            });
    }

    /** A port switched off with the geography above it is no longer live either. */
    public function test_a_port_under_a_stopped_governorate_is_left_off_the_index(): void
    {
        $governorate = Governorate::factory()->create(['is_active' => false]);
        $port = Port::factory()->create(['governorate_id' => $governorate->id, 'name' => 'ميناء محافظة موقوفة']);

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.ports.index'))
            ->assertOk()
            ->assertDontSee($port->name);
    }

    public function test_the_index_filters_by_region_and_search_term(): void
    {
        $match = $this->port('ميناء القطيف');
        $other = $this->port('ميناء ينبع');

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.ports.index', [
                'region_id' => $match->governorate->region_id,
                'q' => 'القطيف',
            ]))
            ->assertOk()
            ->assertSee('ميناء القطيف')
            ->assertDontSee('ميناء ينبع');

        $this->assertNotSame($match->governorate->region_id, $other->governorate->region_id);
    }

    public function test_the_card_opens_a_profile_carrying_the_berths_workforce_and_requests(): void
    {
        $port = $this->port('ميناء الليث');
        HarborBoatCapacity::factory()->create(['port_id' => $port->id, 'boat_type' => 'small', 'capacity' => 4]);
        HarborBoatCapacity::factory()->create(['port_id' => $port->id, 'boat_type' => 'large', 'capacity' => 0, 'status' => 'stopped']);
        Boat::factory()->count(4)->create(['home_port_id' => $port->id, 'boat_type' => 'small', 'harbor_status' => 'occupied']);
        HarborWorker::factory()->create(['port_id' => $port->id, 'worker_type' => 'fisherman', 'employment_status' => 'active']);
        HarborWorker::factory()->create(['port_id' => $port->id, 'worker_type' => 'supervisor', 'employment_status' => 'suspended']);
        HarborLicense::factory()->create(['port_id' => $port->id, 'license_status' => 'valid']);
        HarborViolation::factory()->create(['port_id' => $port->id, 'violation_status' => 'open']);
        InformationSubmission::factory()->count(2)->create(['port_id' => $port->id, 'status' => 'approved']);

        $response = $this->actingAs($this->supervisor())
            ->get(route('information.admin.ports.show', $port))
            ->assertOk()
            ->assertSee('ميناء الليث')
            ->assertSee($port->governorate->name)
            ->assertSee($port->governorate->region->name)
            ->assertSeeInOrder(['القوارب حسب النوع', 'القوى البشرية', 'طلبات الميناء'])
            /** The submission counts open the review queue already filtered to this port. */
            ->assertSee(e(route('information.admin.index', ['port_id' => $port->id, 'status' => 'approved'])), false);

        $response->assertViewHas('capacity', 4)
            ->assertViewHas('occupied', 4)
            ->assertViewHas('occupancy', 100.0)
            ->assertViewHas('activeWorkers', 1)
            ->assertViewHas('validLicenses', 1)
            ->assertViewHas('openViolations', 1)
            ->assertViewHas('boatTypes', function (array $boatTypes): bool {
                $small = collect($boatTypes)->firstWhere('code', 'small');
                $large = collect($boatTypes)->firstWhere('code', 'large');

                return $small['status'] === 'full'
                    && $small['available'] === 0
                    && $large['status'] === 'stopped';
            })
            ->assertViewHas('submissions', fn (array $submissions): bool => collect($submissions)
                ->firstWhere('status', 'approved')['count'] === 2);
    }

    /** A port with no berth record at all reads as empty rather than dividing by zero. */
    public function test_a_port_without_capacity_records_reports_no_occupancy(): void
    {
        $port = $this->port('ميناء بلا سعة');

        $this->actingAs($this->supervisor())
            ->get(route('information.admin.ports.show', $port))
            ->assertOk()
            ->assertViewHas('capacity', 0)
            ->assertViewHas('occupancy', 0.0);
    }

    private function port(string $name, array $attributes = []): Port
    {
        $region = Region::factory()->create();
        $governorate = Governorate::factory()->create(['region_id' => $region->id]);

        return Port::factory()->create([...$attributes, 'governorate_id' => $governorate->id, 'name' => $name]);
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
