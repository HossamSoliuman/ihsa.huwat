<?php

namespace Tests\Feature;

use App\Models\RegistrationRequest;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "انضم إلى حوات": مالك أو دلال يطلب حسابًا من صفحة الهبوط، والمدير العام
 * يعتمده (فيُنشأ الحساب بكلمة المرور التي اختارها) أو يرفضه.
 */
class RegistrationRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'role' => 'owner',
            'name' => 'سالم الحربي',
            'phone' => '+966 55 123 4567',
            'email' => 'salem@example.com',
            'business_name' => 'مؤسسة البحر',
            'city' => 'جدة',
            'boats_count' => 3,
            'notes' => 'أملك ثلاثة قوارب في ميناء جدة.',
            'password' => 'secret-123',
            'password_confirmation' => 'secret-123',
        ];
    }

    public function test_the_landing_page_shows_the_registration_section(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('id="register"', false)
            ->assertSee('مالك قوارب')
            ->assertSee('دلال')
            ->assertSee(route('landing.register'), false);
    }

    public function test_an_owner_submits_a_request_that_waits_for_review(): void
    {
        $this->post(route('landing.register'), $this->payload())
            ->assertRedirect(route('landing').'#register')
            ->assertSessionHas('registered', 'سالم الحربي');

        $request = RegistrationRequest::sole();
        $this->assertSame(RegistrationRequest::PENDING, $request->status);
        $this->assertSame('0551234567', $request->phone);
        $this->assertSame(Role::OWNER, $request->role->key);
        $this->assertSame(3, $request->boats_count);
        $this->assertNotSame('secret-123', $request->getRawOriginal('password'));

        // لا حساب قبل الاعتماد.
        $this->assertDatabaseMissing('users', ['phone' => '0551234567']);
    }

    public function test_a_dalal_request_drops_the_boats_count(): void
    {
        $this->post(route('landing.register'), $this->payload(['role' => 'dalal']))->assertSessionHasNoErrors();

        $request = RegistrationRequest::sole();
        $this->assertSame(Role::DALAL, $request->role->key);
        $this->assertNull($request->boats_count);
    }

    public function test_only_owner_and_dalal_can_register_and_input_is_validated(): void
    {
        $this->post(route('landing.register'), $this->payload(['role' => 'super_admin']))
            ->assertRedirect(route('landing').'#register')
            ->assertSessionHasErrorsIn('register', ['role']);

        $this->post(route('landing.register'), $this->payload(['phone' => '123', 'password_confirmation' => 'other-123']))
            ->assertSessionHasErrorsIn('register', ['phone', 'password']);

        $this->assertDatabaseCount('registration_requests', 0);
    }

    public function test_a_phone_that_has_an_account_or_a_pending_request_is_refused(): void
    {
        User::factory()->owner()->create(['phone' => '0551234567']);

        $this->post(route('landing.register'), $this->payload())->assertSessionHasErrorsIn('register', ['phone']);

        RegistrationRequest::factory()->create(['phone' => '0559999999']);

        $this->post(route('landing.register'), $this->payload(['phone' => '0559999999']))->assertSessionHasErrorsIn('register', ['phone']);

        // طلب مرفوض لا يمنع التقدّم من جديد.
        RegistrationRequest::factory()->create(['phone' => '0558888888', 'status' => RegistrationRequest::REJECTED]);

        $this->post(route('landing.register'), $this->payload(['phone' => '0558888888']))->assertSessionHasNoErrors();
    }

    public function test_only_a_super_admin_sees_the_review_page(): void
    {
        $this->get(route('panel.registrations'))->assertRedirect(route('panel.login'));

        $this->actingAs(User::factory()->owner()->create())
            ->get(route('panel.registrations'))
            ->assertForbidden();

        RegistrationRequest::factory()->create(['name' => 'طلب معلّق']);
        RegistrationRequest::factory()->create(['name' => 'طلب مرفوض', 'status' => RegistrationRequest::REJECTED]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('panel.registrations'))
            ->assertOk()
            ->assertSee('طلب معلّق')
            ->assertDontSee('طلب مرفوض');
    }

    public function test_approving_creates_the_account_with_the_chosen_password(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $this->post(route('landing.register'), $this->payload(['role' => 'dalal']));
        $request = RegistrationRequest::sole();

        $this->actingAs($admin)
            ->post(route('panel.registrations.approve', $request))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $user = User::where('phone', '0551234567')->sole();
        $this->assertTrue($user->hasAppRole(Role::DALAL));
        $this->assertTrue($user->active);

        $request->refresh();
        $this->assertSame(RegistrationRequest::APPROVED, $request->status);
        $this->assertTrue($request->user->is($user));
        $this->assertTrue($request->reviewer->is($admin));

        // يدخل بكلمة المرور التي اختارها في طلبه.
        $this->post(route('panel.logout'));
        $this->post(route('panel.login.store'), ['identifier' => '0551234567', 'password' => 'secret-123'])
            ->assertRedirect(route('panel.home'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_rejecting_keeps_the_reason_and_creates_no_account(): void
    {
        $request = RegistrationRequest::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('panel.registrations.reject', $request), ['rejection_reason' => 'بيانات ناقصة'])
            ->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame(RegistrationRequest::REJECTED, $request->status);
        $this->assertSame('بيانات ناقصة', $request->rejection_reason);
        $this->assertDatabaseMissing('users', ['phone' => $request->phone]);
    }

    public function test_a_request_is_reviewed_once_and_never_over_an_existing_account(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $reviewed = RegistrationRequest::factory()->create(['status' => RegistrationRequest::REJECTED]);

        $this->actingAs($admin)
            ->post(route('panel.registrations.approve', $reviewed))
            ->assertSessionHasErrors('review');

        $request = RegistrationRequest::factory()->create();
        User::factory()->owner()->create(['phone' => $request->phone]);

        $this->post(route('panel.registrations.approve', $request))->assertSessionHasErrors('review');
        $this->assertTrue($request->fresh()->isPending());
    }
}
