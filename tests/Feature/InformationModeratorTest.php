<?php

namespace Tests\Feature;

use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\Governorate;
use App\Models\InformationSubmission;
use App\Models\Port;
use App\Models\Region;
use App\Models\Role;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InformationModeratorTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_desk_opens_a_moderator_account_with_the_records_it_reaches(): void
    {
        $first = $this->port('ميناء المشرف الأول');
        $second = $this->port('ميناء المشرف الثاني');

        $this->actingAs($this->supervisor())
            ->post(route('information.admin.moderators.store'), [
                'full_name' => 'سالم أحمد الزهراني',
                'username' => 'salem.alzahrani',
                'email' => 'salem@hawat.sa',
                'phone' => '0551234567',
                'national_id' => '1012345678',
                'password' => 'a-very-strong-secret',
                'is_active' => '1',
                'scope_type' => UserScope::TYPE_PORT,
                'scope_ids' => [UserScope::TYPE_PORT => [$first->id, $second->id]],
            ])
            ->assertSessionHasNoErrors();

        $moderator = User::query()->where('username', 'salem.alzahrani')->sole();

        $this->assertSame('information_moderator', $moderator->role()->value('code'));
        $this->assertSame('0551234567', $moderator->phone);
        $this->assertSame('1012345678', $moderator->national_id);
        $this->assertTrue(Hash::check('a-very-strong-secret', $moderator->password_hash));
        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            $moderator->assignedScopes()->pluck('scope_id')->all(),
        );
    }

    /** The lists not chosen submit alongside the chosen one and must not widen the account. */
    public function test_only_the_chosen_levels_records_are_filed(): void
    {
        $port = $this->port('ميناء الاختيار');
        $market = $this->market('سوق غير مختار');

        $this->actingAs($this->supervisor())
            ->post(route('information.admin.moderators.store'), [
                ...$this->accountPayload(),
                'scope_type' => UserScope::TYPE_PORT,
                'scope_ids' => [
                    UserScope::TYPE_PORT => [$port->id],
                    UserScope::TYPE_MARKET => [$market->id],
                ],
            ])
            ->assertSessionHasNoErrors();

        $scopes = User::query()->where('username', 'test.moderator')->sole()->assignedScopes;

        $this->assertCount(1, $scopes);
        $this->assertSame(UserScope::TYPE_PORT, $scopes->first()->scope_type);
        $this->assertSame($port->id, $scopes->first()->scope_id);
    }

    public function test_editing_replaces_the_assignments_and_keeps_the_password_when_left_blank(): void
    {
        $held = $this->port('ميناء محتفظ به');
        $dropped = $this->port('ميناء مسحوب');
        $moderator = $this->moderator(UserScope::TYPE_PORT, [$held->id, $dropped->id]);
        $originalHash = $moderator->password_hash;

        $this->actingAs($this->supervisor())
            ->patch(route('information.admin.moderators.update', $moderator), [
                ...$this->accountPayload(),
                'username' => $moderator->username,
                'password' => '',
                'scope_type' => UserScope::TYPE_PORT,
                'scope_ids' => [UserScope::TYPE_PORT => [$held->id]],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame([$held->id], $moderator->assignedScopes()->pluck('scope_id')->all());
        $this->assertSame($originalHash, $moderator->fresh()->password_hash);
    }

    public function test_the_desk_screens_list_and_open_a_moderator_account(): void
    {
        $port = $this->port('ميناء الشاشة');
        $moderator = $this->moderator(UserScope::TYPE_PORT, [$port->id]);
        $desk = $this->supervisor();

        $this->actingAs($desk)
            ->get(route('information.admin.moderators.index'))
            ->assertOk()
            ->assertSee($moderator->full_name)
            ->assertSee('ميناء الشاشة');

        $this->actingAs($desk)->get(route('information.admin.moderators.create'))->assertOk();

        $this->actingAs($desk)
            ->get(route('information.admin.moderators.show', $moderator))
            ->assertOk()
            ->assertSee($moderator->username);

        $this->actingAs($desk)
            ->delete(route('information.admin.moderators.destroy', $moderator))
            ->assertRedirect(route('information.admin.moderators.index'));

        $this->assertDatabaseMissing('users', ['id' => $moderator->id]);
        $this->assertDatabaseMissing('user_scopes', ['user_id' => $moderator->id]);
    }

    /** The screen edits moderator accounts only, so a hand-typed id cannot rewrite the desk's own. */
    public function test_the_moderators_screen_refuses_an_account_that_is_not_a_moderator(): void
    {
        $desk = $this->supervisor();

        $this->actingAs($desk)->get(route('information.admin.moderators.show', $desk))->assertNotFound();
        $this->actingAs($desk)->delete(route('information.admin.moderators.destroy', $desk))->assertNotFound();
    }

    public function test_a_moderator_signing_in_lands_on_the_information_centre(): void
    {
        $moderator = $this->moderator(UserScope::TYPE_PORT, [$this->port('ميناء الدخول')->id]);

        $this->post(route('login.store'), ['username' => $moderator->username, 'password' => 'password'])
            ->assertRedirect(route('information.admin.dashboard'));
    }

    public function test_a_moderator_never_reaches_the_accounts_or_the_reference_lists(): void
    {
        $moderator = $this->moderator(UserScope::TYPE_PORT, [$this->port('ميناء المشرف')->id]);

        $this->actingAs($moderator)->get(route('information.admin.moderators.index'))->assertForbidden();
        $this->actingAs($moderator)->get(route('information.admin.lookups.index'))->assertForbidden();
        $this->actingAs($moderator)->post(route('information.admin.moderators.store'), [])->assertForbidden();
    }

    public function test_a_port_moderator_reads_only_its_own_ports_and_submissions(): void
    {
        $held = $this->port('ميناء ضمن النطاق');
        $other = $this->port('ميناء خارج النطاق');
        $moderator = $this->moderator(UserScope::TYPE_PORT, [$held->id]);

        $mine = InformationSubmission::factory()->create(['port_id' => $held->id]);
        $theirs = InformationSubmission::factory()->create(['port_id' => $other->id]);

        $this->actingAs($moderator)
            ->get(route('information.admin.ports.index'))
            ->assertOk()
            ->assertSee('ميناء ضمن النطاق')
            ->assertDontSee('ميناء خارج النطاق');

        $this->actingAs($moderator)->get(route('information.admin.ports.show', $held))->assertOk();
        $this->actingAs($moderator)->get(route('information.admin.ports.show', $other))->assertNotFound();

        $this->actingAs($moderator)
            ->get(route('information.admin.index'))
            ->assertOk()
            ->assertViewHas('submissions', fn ($submissions): bool => $submissions->pluck('id')->all() === [$mine->id]);

        $this->actingAs($moderator)->get(route('information.admin.show', $mine))->assertOk();
        $this->actingAs($moderator)->get(route('information.admin.show', $theirs))->assertNotFound();
    }

    /** Reading a submission is within a moderator's reach; ruling on one is not. */
    public function test_a_moderator_cannot_review_a_submission_in_its_own_scope(): void
    {
        $port = $this->port('ميناء المراجعة');
        $moderator = $this->moderator(UserScope::TYPE_PORT, [$port->id]);
        $submission = InformationSubmission::factory()->create(['port_id' => $port->id, 'status' => 'submitted']);

        $this->actingAs($moderator)
            ->patch(route('information.admin.review', $submission), ['status' => 'approved'])
            ->assertForbidden();
    }

    public function test_a_port_moderator_is_shut_out_of_the_market_registries(): void
    {
        $moderator = $this->moderator(UserScope::TYPE_PORT, [$this->port('ميناء بلا أسواق')->id]);

        $this->actingAs($moderator)->get(route('information.admin.markets.index'))->assertForbidden();
        $this->actingAs($moderator)->get(route('information.admin.brokers.index'))->assertForbidden();
    }

    public function test_a_market_moderator_reads_only_its_own_market_and_opens_no_new_one(): void
    {
        $held = $this->market('سوق ضمن النطاق');
        $other = $this->market('سوق خارج النطاق');
        $moderator = $this->moderator(UserScope::TYPE_MARKET, [$held->id]);

        $mine = FishMarketBroker::factory()->create(['fish_market_id' => $held->id]);
        FishMarketBroker::factory()->create(['fish_market_id' => $other->id]);

        $this->actingAs($moderator)
            ->get(route('information.admin.markets.index'))
            ->assertOk()
            ->assertSee('سوق ضمن النطاق')
            ->assertDontSee('سوق خارج النطاق');

        $this->actingAs($moderator)->get(route('information.admin.markets.show', $held))->assertOk();
        $this->actingAs($moderator)->get(route('information.admin.markets.show', $other))->assertNotFound();
        $this->actingAs($moderator)->get(route('information.admin.ports.index'))->assertForbidden();

        /** An account pinned to named markets manages them and adds none. */
        $this->actingAs($moderator)->get(route('information.admin.markets.create'))->assertForbidden();

        $this->actingAs($moderator)
            ->get(route('information.admin.brokers.index'))
            ->assertOk()
            ->assertViewHas('brokers', fn ($brokers): bool => $brokers->pluck('id')->all() === [$mine->id]);
    }

    public function test_a_broker_cannot_be_filed_against_a_market_outside_the_scope(): void
    {
        $held = $this->market('سوق الدلال');
        $other = $this->market('سوق ممنوع');
        $moderator = $this->moderator(UserScope::TYPE_MARKET, [$held->id]);

        $this->actingAs($moderator)
            ->post(route('information.admin.brokers.store'), [
                'fish_market_id' => $other->id,
                'entity_type' => FishMarketBroker::TYPE_INDIVIDUAL,
                'full_name' => 'دلال خارج النطاق',
                'national_id' => '1012345678',
                'phone' => '0551234567',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('fish_market_id');
    }

    /** A region holds whatever sits inside it, so its ports and markets follow from it. */
    public function test_a_region_moderator_reaches_everything_inside_its_region_and_nothing_outside(): void
    {
        $region = Region::factory()->create(['name' => 'منطقة المشرف']);
        $governorate = Governorate::factory()->create(['region_id' => $region->id, 'name' => 'محافظة المشرف']);
        $insidePort = Port::factory()->create(['governorate_id' => $governorate->id, 'name' => 'ميناء داخل المنطقة']);
        $insideMarket = FishMarket::factory()->create(['governorate_id' => $governorate->id, 'name' => 'سوق داخل المنطقة']);

        $outsidePort = $this->port('ميناء خارج المنطقة');
        $outsideMarket = $this->market('سوق خارج المنطقة');

        $moderator = $this->moderator(UserScope::TYPE_REGION, [$region->id]);

        $this->actingAs($moderator)->get(route('information.admin.ports.show', $insidePort))->assertOk();
        $this->actingAs($moderator)->get(route('information.admin.ports.show', $outsidePort))->assertNotFound();
        $this->actingAs($moderator)->get(route('information.admin.markets.show', $insideMarket))->assertOk();
        $this->actingAs($moderator)->get(route('information.admin.markets.show', $outsideMarket))->assertNotFound();

        /** A region grows new markets, so opening one inside it is within reach. */
        $this->actingAs($moderator)->get(route('information.admin.markets.create'))->assertOk();
    }

    public function test_a_moderator_whose_assignments_were_all_removed_reaches_no_registry(): void
    {
        $role = Role::query()->where('code', 'information_moderator')->firstOrFail();
        $moderator = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($moderator)->get(route('information.admin.index'))->assertForbidden();
        $this->actingAs($moderator)->get(route('information.admin.ports.index'))->assertForbidden();
        $this->actingAs($moderator)->get(route('information.admin.markets.index'))->assertForbidden();
        /** The dashboard is the one landing every account keeps; it simply counts nothing. */
        $this->actingAs($moderator)->get(route('information.admin.dashboard'))->assertOk();
    }

    public function test_the_sidebar_offers_a_moderator_only_the_sections_its_level_answers_for(): void
    {
        $moderator = $this->moderator(UserScope::TYPE_MARKET, [$this->market('سوق القائمة الجانبية')->id]);

        /** The nav links are asserted as markup: the words themselves also head panels on the page. */
        $this->actingAs($moderator)
            ->get(route('information.admin.dashboard'))
            ->assertOk()
            ->assertSee('<span>أسواق السمك</span>', false)
            ->assertSee('<span>الدلالين</span>', false)
            ->assertDontSee('<span>الموانئ</span>', false)
            ->assertDontSee('<span>المشرفون</span>', false)
            ->assertDontSee('<span>الإعدادات</span>', false);
    }

    public function test_the_sidebar_offers_the_desk_every_section(): void
    {
        $this->actingAs($this->supervisor())
            ->get(route('information.admin.dashboard'))
            ->assertOk()
            ->assertSee('<span>الموانئ</span>', false)
            ->assertSee('<span>المشرفون</span>', false)
            ->assertSee('<span>الإعدادات</span>', false);
    }

    /** @return array<string, string> */
    private function accountPayload(): array
    {
        return [
            'full_name' => 'مشرف الاختبار',
            'username' => 'test.moderator',
            'phone' => '0559876543',
            'national_id' => '2012345678',
            'password' => 'a-very-strong-secret',
            'is_active' => '1',
        ];
    }

    /** @param  list<int>  $scopeIds */
    private function moderator(string $scopeType, array $scopeIds): User
    {
        $role = Role::query()->where('code', 'information_moderator')->firstOrFail();
        $moderator = User::factory()->create(['role_id' => $role->id]);

        foreach ($scopeIds as $scopeId) {
            UserScope::factory()->forRecord($scopeType, $scopeId)->create(['user_id' => $moderator->id]);
        }

        return $moderator;
    }

    private function port(string $name): Port
    {
        return Port::factory()->create(['governorate_id' => $this->governorate()->id, 'name' => $name]);
    }

    private function market(string $name): FishMarket
    {
        return FishMarket::factory()->create(['governorate_id' => $this->governorate()->id, 'name' => $name]);
    }

    /** Named apart from the seeded geography, which the factory would otherwise collide with. */
    private function governorate(): Governorate
    {
        $region = Region::factory()->create(['name' => 'منطقة اختبار '.fake()->unique()->numerify('####')]);

        return Governorate::factory()->create([
            'region_id' => $region->id,
            'name' => 'محافظة اختبار '.fake()->unique()->numerify('####'),
        ]);
    }

    private function supervisor(): User
    {
        $role = Role::query()->where('code', 'quality_supervisor')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
