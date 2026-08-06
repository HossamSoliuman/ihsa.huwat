<?php

namespace Tests\Feature;

use App\Models\FishFamily;
use App\Models\FishSpecies;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FishCatalogueTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_national_coding_sheet_is_seeded_in_full(): void
    {
        $this->assertSame(47, FishFamily::query()->count());
        $this->assertSame(254, FishSpecies::query()->whereNotNull('code')->count());

        $kingMackerel = FishSpecies::query()->where('code', 5104)->sole();

        $this->assertSame('Scomberomorus commerson', $kingMackerel->scientific_name);
        $this->assertSame('Narrow - barred Spanish mackerel', $kingMackerel->english_name);
        $this->assertSame('كنعد كبير / كنعد صغير', $kingMackerel->local_name_gulf);
        $this->assertSame('دراك (كبير) / ضرس (صغير)', $kingMackerel->local_name_red_sea);
        $this->assertSame('Scombridae', $kingMackerel->family->scientific_name);
    }

    public function test_every_coded_species_is_filed_under_the_family_its_code_belongs_to(): void
    {
        $this->assertSame(0, FishSpecies::query()->whereNotNull('code')->whereNull('fish_family_id')->count());

        FishSpecies::query()->whereNotNull('code')->with('family')->get()
            ->each(fn (FishSpecies $species) => $this->assertSame(
                intdiv($species->code, 100) * 100,
                $species->family->code,
                "Species {$species->code} is filed under family {$species->family->code}.",
            ));
    }

    public function test_the_two_codes_mistyped_in_the_sheet_are_filed_where_the_sequence_puts_them(): void
    {
        /** The sheet carries 39011 for this one, which is 3911 between 3910 and 3912. */
        $this->assertSame('Caranx sexfasciatus', FishSpecies::query()->where('code', 3911)->value('scientific_name'));

        /** And it repeats 3923, so the Carangidae catch-all takes the next free code. */
        $this->assertSame('Carangidae nei', FishSpecies::query()->where('code', 3924)->value('scientific_name'));
    }

    public function test_a_species_takes_its_short_name_from_the_local_names_the_sheet_records(): void
    {
        /** Gulf name first, and only its first alias — dashboards print one name. */
        $this->assertSame('حمرا صابغ', FishSpecies::query()->where('code', 1604)->value('name_ar'));

        /** No Gulf name recorded, so the Red Sea one stands in. */
        $this->assertSame('رقع', FishSpecies::query()->where('code', 1501)->value('name_ar'));

        /** Neither coast names it, so the scientific name carries the row. */
        $this->assertSame('Penaeus latisulcatus', FishSpecies::query()->where('code', 5705)->value('name_ar'));
    }

    public function test_local_names_may_repeat_across_the_catalogue(): void
    {
        $this->assertGreaterThan(1, FishSpecies::query()->where('name_ar', 'حمام')->count());
    }

    public function test_the_species_desk_lists_the_catalogue_with_its_codes(): void
    {
        $this->actingAs($this->supervisor())
            ->get(route('information.admin.lookups.index', ['tab' => 'species']))
            ->assertOk()
            ->assertSee('الترميز')
            ->assertSee('Scomberomorus commerson')
            ->assertSee('5104');
    }

    public function test_a_species_added_by_hand_carries_no_code_and_sorts_after_the_sheet(): void
    {
        $species = FishSpecies::factory()->create(['name_ar' => 'نوع مضاف يدوياً']);

        $this->assertNull($species->code);
        $this->assertNull($species->fish_family_id);

        $ordered = FishSpecies::query()->ordered()->get();

        $this->assertSame(1001, $ordered->first()->code);
        $this->assertNull($ordered->last()->code);
    }

    private function supervisor(): User
    {
        $role = Role::query()->where('code', 'quality_supervisor')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
