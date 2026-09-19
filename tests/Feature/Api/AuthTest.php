<?php

namespace Tests\Feature\Api;

use App\Models\OtpCode;
use App\Models\Role;
use App\Models\User;
use App\Services\Sms\ArraySmsSender;
use App\Services\Sms\SmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * واجهة الدخول في التطبيق: الجوال وكلمة المرور يُصدران رمز Sanctum، والحساب
 * الحالي وتغيير كلمة المرور خلفه، واستعادة كلمة المرور بالجوال على ثلاث خطوات.
 * الرسائل تُلتقط في الذاكرة (ArraySmsSender) ليُقرأ منها الرمز.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private ArraySmsSender $sms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sms = new ArraySmsSender;
        $this->app->instance(SmsSender::class, $this->sms);
    }

    private function owner(array $attributes = []): User
    {
        return User::factory()->owner()->create(['phone' => '0512345678', 'password' => 'secret-123'] + $attributes);
    }

    /**
     * حارس Sanctum يحتفظ بالمستخدم بين طلبات الاختبار الواحد (في الإنتاج لكل
     * طلب تطبيقه)، فيُنسى قبل الطلب الذي يجب أن يُقيَّم رمزه من جديد.
     */
    private function fresh(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }

    private function codeSentTo(string $phone): string
    {
        preg_match('/\d{6}/', (string) $this->sms->lastTo($phone), $m);

        return $m[0];
    }

    public function test_an_owner_logs_in_with_phone_and_gets_a_bearer_token(): void
    {
        $owner = $this->owner();

        $response = $this->postJson('/api/v1/auth/login', [
            'phone' => '+966 512345678',
            'password' => 'secret-123',
            'device_name' => 'Pixel',
            'fcm_token' => 'fcm-abc',
        ])->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.phone', '0512345678')
            ->assertJsonPath('data.user.role.key', Role::OWNER)
            ->assertJsonMissingPath('data.user.password');

        $token = $response->json('data.token');

        $this->assertSame('fcm-abc', $owner->fresh()->fcm_token);
        $this->assertSame('Pixel', $owner->tokens()->first()->name);

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $owner->id)
            ->assertJsonPath('data.role.name', 'مالك القارب');
    }

    public function test_bad_credentials_inactive_accounts_and_ministry_users_are_refused(): void
    {
        $this->owner();

        $this->postJson('/api/v1/auth/login', ['phone' => '0512345678', 'password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone' => 'بيانات الدخول غير صحيحة.']);

        User::factory()->owner()->inactive()->create(['phone' => '0500000009', 'password' => 'secret-123']);
        $this->postJson('/api/v1/auth/login', ['phone' => '0500000009', 'password' => 'secret-123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone' => 'هذا الحساب معطّل — راجع الإدارة.']);

        User::factory()->create(['phone' => '0500000008', 'password' => 'secret-123']);
        $this->postJson('/api/v1/auth/login', ['phone' => '0500000008', 'password' => 'secret-123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone' => 'ليس لهذا الحساب دور في التطبيق.']);

        // بلا رمز: JSON لا تحويل إلى صفحة دخول.
        $this->getJson('/api/v1/auth/me')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
        $this->get('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_logout_revokes_only_the_current_device(): void
    {
        $owner = $this->owner();
        $phone = $owner->createToken('phone')->plainTextToken;
        $tablet = $owner->createToken('tablet')->plainTextToken;

        $this->withToken($phone)->postJson('/api/v1/auth/logout')->assertOk();

        $this->assertSame(1, $owner->tokens()->count());
        $this->fresh()->withToken($phone)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->fresh()->withToken($tablet)->getJson('/api/v1/auth/me')->assertOk();
    }

    public function test_a_deactivated_account_loses_its_token_on_the_next_call(): void
    {
        $owner = $this->owner();
        Sanctum::actingAs($owner);

        $owner->update(['active' => false]);

        $this->getJson('/api/v1/auth/me')->assertForbidden();
    }

    public function test_profile_update_and_password_change(): void
    {
        $owner = $this->owner();
        $keep = $owner->createToken('phone')->plainTextToken;
        $owner->createToken('old-tablet');

        $this->withToken($keep)->putJson('/api/v1/auth/me', ['name' => 'سالم', 'email' => 'salem@example.com', 'locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.name', 'سالم')
            ->assertJsonPath('data.locale', 'en');

        $this->withToken($keep)->postJson('/api/v1/auth/change-password', [
            'current_password' => 'wrong',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertUnprocessable()->assertJsonValidationErrors('current_password');

        $this->withToken($keep)->postJson('/api/v1/auth/change-password', [
            'current_password' => 'secret-123',
            'password' => 'new-secret-1',
            'password_confirmation' => 'new-secret-1',
        ])->assertOk();

        $this->assertTrue(password_verify('new-secret-1', $owner->fresh()->password));
        // الجهاز الحالي يبقى، والآخر يخرج.
        $this->assertSame(1, $owner->tokens()->count());
        $this->withToken($keep)->getJson('/api/v1/auth/me')->assertOk();

        $this->withToken($keep)->postJson('/api/v1/auth/fcm-token', ['fcm_token' => 'fcm-new'])->assertOk();
        $this->assertSame('fcm-new', $owner->fresh()->fcm_token);
    }

    public function test_password_reset_by_phone_otp(): void
    {
        $owner = $this->owner();
        $owner->createToken('phone');

        // الردّ واحد وُجد الجوال أم لا، ولا رسالة لجوال غير مسجّل.
        $this->postJson('/api/v1/auth/forgot-password', ['phone' => '0599999999'])->assertOk();
        $this->assertNull($this->sms->lastTo('0599999999'));

        $this->postJson('/api/v1/auth/forgot-password', ['phone' => '0512345678'])
            ->assertOk()
            ->assertJsonPath('data.expires_in', OtpCode::TTL_MINUTES * 60);

        $code = $this->codeSentTo('0512345678');

        $this->postJson('/api/v1/auth/verify-otp', ['phone' => '0512345678', 'code' => '000000'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code' => 'الرمز غير صحيح.']);

        $this->postJson('/api/v1/auth/verify-otp', ['phone' => '0512345678', 'code' => $code])
            ->assertOk()
            ->assertJsonPath('data.verified', true);

        $this->postJson('/api/v1/auth/reset-password', [
            'phone' => '0512345678',
            'code' => $code,
            'password' => 'brand-new-1',
            'password_confirmation' => 'brand-new-1',
        ])->assertOk();

        $this->assertTrue(password_verify('brand-new-1', $owner->fresh()->password));
        $this->assertSame(0, $owner->tokens()->count());

        // الرمز استُهلك: لا يُعاد استعماله.
        $this->postJson('/api/v1/auth/reset-password', [
            'phone' => '0512345678',
            'code' => $code,
            'password' => 'another-one-1',
            'password_confirmation' => 'another-one-1',
        ])->assertUnprocessable()->assertJsonValidationErrors('code');
    }

    public function test_an_otp_locks_after_five_wrong_attempts_and_expires(): void
    {
        $this->owner();

        $this->postJson('/api/v1/auth/forgot-password', ['phone' => '0512345678'])->assertOk();

        for ($i = 0; $i < OtpCode::MAX_ATTEMPTS; $i++) {
            $this->postJson('/api/v1/auth/verify-otp', ['phone' => '0512345678', 'code' => '000000'])->assertUnprocessable();
        }

        $code = $this->codeSentTo('0512345678');

        $this->postJson('/api/v1/auth/verify-otp', ['phone' => '0512345678', 'code' => $code])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code' => 'تجاوزت عدد المحاولات — اطلب رمزًا جديدًا.']);

        OtpCode::query()->update(['attempts' => 0, 'expires_at' => now()->subMinute()]);

        $this->postJson('/api/v1/auth/verify-otp', ['phone' => '0512345678', 'code' => $code])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code' => 'انتهت صلاحية الرمز — اطلب رمزًا جديدًا.']);
    }
}
