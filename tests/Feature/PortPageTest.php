<?php

namespace Tests\Feature;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\Fisher;
use App\Models\FishingSite;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use App\Models\Species;
use App\Models\StatisticsOfficer;
use App\Models\Trip;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * صفحة الميناء الواحد تجمع ما كان مبعثرًا على شاشات الأسطول والصيادين والرحلات.
 * الحراسة هنا على أمرين: أن بطاقة الميناء في القائمة تفتحها، وأن الصفحة لا تعرض
 * إلا سجلات ميناءها — فالتسريب بين موانئ متجاورة لا يظهر في التصفّح العادي.
 */
class PortPageTest extends TestCase
{
    use RefreshDatabase;

    private Port $port;

    private Port $other;

    protected function setUp(): void
    {
        parent::setUp();

        $region = Region::create(['name' => 'المنطقة الشرقية (اختبار)', 'code' => 'EST']);
        $governorate = Governorate::create(['region_id' => $region->id, 'name' => 'القطيف (اختبار)']);

        $this->port = Port::create([
            'governorate_id' => $governorate->id,
            'name' => 'ميناء القطيف (اختبار)',
            'code' => 'PQTF',
            'boats_count' => 4,
            'active_boats' => 3,
            'fishers_count' => 2,
            'statistics_staff' => 1,
        ]);

        $this->other = Port::create([
            'governorate_id' => $governorate->id,
            'name' => 'ميناء دارين (اختبار)',
        ]);
    }

    public function test_the_port_card_in_the_list_links_to_the_port_page(): void
    {
        $this->get(route('ports'))
            ->assertOk()
            ->assertSee('href="'.route('ports.show', $this->port).'"', false);
    }

    public function test_the_page_gathers_the_records_registered_under_the_port(): void
    {
        Boat::create(['port_id' => $this->port->id, 'name' => 'قارب النور', 'boat_number' => 'B-100']);
        Fisher::create(['port_id' => $this->port->id, 'name' => 'سالم القحطاني', 'national_id' => '1234567890']);
        StatisticsOfficer::create(['port_id' => $this->port->id, 'name' => 'ماجد الدوسري', 'employee_number' => 'E-1']);
        FishingSite::create(['port_id' => $this->port->id, 'name' => 'موقع أبو علي']);

        $this->get(route('ports.show', $this->port))
            ->assertOk()
            ->assertSee($this->port->name)
            ->assertSee('قارب النور')
            ->assertSee('سالم القحطاني')
            ->assertSee('ماجد الدوسري')
            ->assertSee('موقع أبو علي');
    }

    public function test_the_page_counts_only_the_catch_of_its_own_departures(): void
    {
        $species = Species::create(['name_ar' => 'الهامور (اختبار)']);

        foreach ([[$this->port, 'T-1', 700], [$this->other, 'T-2', 90]] as [$port, $number, $kg]) {
            $boat = Boat::create(['port_id' => $port->id, 'name' => 'قارب '.$number, 'boat_number' => $number]);

            $trip = Trip::create([
                'trip_number' => $number,
                'boat_id' => $boat->id,
                'departure_port_id' => $port->id,
                'departure_time' => now()->subDay(),
            ]);

            CatchRecord::create([
                'trip_id' => $trip->id,
                'species_id' => $species->id,
                'quantity_kg' => $kg,
                'recorded_at' => now()->subDay()->toDateString(),
            ]);
        }

        $this->get(route('ports.show', $this->port))
            ->assertOk()
            ->assertSee('T-1')
            ->assertDontSee('T-2')
            ->assertSee('700');
    }

    public function test_a_port_without_records_still_opens(): void
    {
        $this->get(route('ports.show', $this->other))
            ->assertOk()
            ->assertSee('لا توجد سجلات مصيد لهذا الميناء');
    }
}
