<?php

namespace Tests\Feature\Api;

use App\Models\CounterApplication;
use App\Models\Governorate;
use App\Models\HiringRound;
use App\Models\OperatingCompany;
use App\Models\Port;
use App\Models\Role;
use App\Models\User;
use App\Services\Counters\CounterApplicationReview;
use App\Services\Sms\ArraySmsSender;
use App\Services\Sms\SmsSender;
use Database\Seeders\LookupSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spectator\Spectator;
use Tests\TestCase;

/**
 * التقديم لوظيفة عدّاد من التطبيق بلا حساب: الجولات المفتوحة، والتقديم،
 * والتوثيق بالرمز، والمتابعة والسحب بالمفتاح — ثم الدخول بعد اعتماد الشركة.
 * كل ردّ يُطابَق مع docs/api/openapi.yaml (Spectator).
 */
class CounterApplicationApiTest extends TestCase
{
    use RefreshDatabase;

    private ArraySmsSender $sms;

    private HiringRound $round;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(LookupSeeder::class);

        $this->sms = new ArraySmsSender;
        $this->app->instance(SmsSender::class, $this->sms);

        Spectator::using('openapi.yaml')->setPathPrefix('/api/v1');

        $port = Port::factory()->create(['name' => 'ميناء القطيف (اختبار)', 'governorate_id' => Governorate::factory()]);
        $company = OperatingCompany::factory()->operating($port)->create();
        $this->round = HiringRound::factory()->atPortOf($company, $port)->create(['title' => 'جولة الاختبار']);
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'hiring_round_id' => $this->round->id,
            'name' => 'سعد القحطاني',
            'phone' => '0557654321',
            'national_id' => '1098765432',
            'experience_years' => 2,
            'password' => 'secret-123',
            'password_confirmation' => 'secret-123',
        ];
    }

    private function code(): string
    {
        preg_match('/\d{6}/', (string) $this->sms->lastTo('0557654321'), $m);

        return $m[0];
    }

    public function test_it_lists_only_open_rounds(): void
    {
        HiringRound::factory()->atPortOf($this->round->company, $this->round->port)->draft()->create();

        $this->getJson('/api/v1/hiring-rounds')
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'جولة الاختبار')
            ->assertJsonPath('data.0.port.name', 'ميناء القطيف (اختبار)')
            ->assertJsonPath('data.0.seats_left', 3);
    }

    public function test_an_applicant_applies_verifies_and_logs_in_after_approval(): void
    {
        $token = $this->postJson('/api/v1/counter-applications', $this->payload())
            ->assertValidRequest()->assertValidResponse(201)
            ->assertJsonPath('data.status', CounterApplication::PENDING)
            ->assertJsonPath('data.phone_verified', false)
            ->assertJsonPath('meta.expires_in', 600)
            ->assertJsonMissingPath('data.password')
            ->json('data.token');

        $this->postJson("/api/v1/counter-applications/{$token}/verify", ['code' => '000000'])
            ->assertValidRequest()->assertValidResponse(422);

        $this->postJson("/api/v1/counter-applications/{$token}/verify", ['code' => $this->code()])
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.phone_verified', true);

        $application = CounterApplication::sole();
        $staff = User::factory()->company($application->company)->create();
        app(CounterApplicationReview::class)->approve($application, $staff);

        $this->getJson("/api/v1/counter-applications/{$token}")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', CounterApplication::APPROVED);

        $this->postJson('/api/v1/auth/login', ['phone' => '0557654321', 'password' => 'secret-123', 'device_name' => 'Pixel'])
            ->assertOk()
            ->assertJsonPath('data.user.role.key', Role::COUNTER);
    }

    public function test_resend_and_withdraw(): void
    {
        $token = $this->postJson('/api/v1/counter-applications', $this->payload())->json('data.token');

        $this->postJson("/api/v1/counter-applications/{$token}/resend")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.expires_in', 600);
        $this->assertCount(2, $this->sms->sent);

        $this->postJson("/api/v1/counter-applications/{$token}/withdraw")
            ->assertValidRequest()->assertValidResponse(200)
            ->assertJsonPath('data.status', CounterApplication::WITHDRAWN);

        $this->postJson("/api/v1/counter-applications/{$token}/withdraw")
            ->assertValidRequest()->assertValidResponse(422);
    }

    public function test_validation_and_unknown_tokens(): void
    {
        $this->postJson('/api/v1/counter-applications', $this->payload(['national_id' => '123', 'phone' => '12']))
            ->assertValidRequest()->assertValidResponse(422)
            ->assertJsonValidationErrors(['national_id', 'phone']);

        $this->getJson('/api/v1/counter-applications/not-a-token')
            ->assertValidRequest()->assertValidResponse(404);
    }
}
