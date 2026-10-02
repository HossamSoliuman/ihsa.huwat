<?php

namespace App\Services\Counters;

use App\Models\CounterApplication;
use App\Models\HiringRound;
use App\Models\OtpCode;
use App\Services\Notifications\Notifier;
use App\Services\Sms\SmsSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * تقديم طلب توظيف عدّاد — من صفحة /counter/apply ومن التطبيق بالخطوات نفسها:
 * التقديم يحفظ الطلب ويرسل رمزًا إلى الجوال، والتوثيق بالرمز يُوصل الطلب
 * إلى الشركة ويبلّغ موظفيها، ثم يتابعه المتقدّم بمفتاحه أو يسحبه.
 */
class CounterApplicationService
{
    public function __construct(
        private readonly SmsSender $sms,
        private readonly Notifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $data  ما تحقّق منه CounterApplicationRequest
     */
    public function submit(array $data, ?string $ip = null): CounterApplication
    {
        $application = DB::transaction(function () use ($data, $ip) {
            $round = HiringRound::whereKey($data['hiring_round_id'])->lockForUpdate()->firstOrFail();

            if (! $round->isAccepting()) {
                throw ValidationException::withMessages(['hiring_round_id' => 'هذه الجولة لا تستقبل طلبات الآن — اختر ميناءً آخر.']);
            }

            // طلب سابق لم يُوثَّق جواله يُستبدل: صاحبه أعاد التقديم بعد أن ضاع رمزه.
            CounterApplication::where('phone', $data['phone'])
                ->where('status', CounterApplication::PENDING)
                ->whereNull('phone_verified_at')
                ->delete();

            return CounterApplication::create([
                'hiring_round_id' => $round->id,
                'operating_company_id' => $round->operating_company_id,
                'port_id' => $round->port_id,
                'token' => Str::random(48),
                'name' => $data['name'],
                'phone' => $data['phone'],
                'national_id' => $data['national_id'],
                'email' => $data['email'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'experience_years' => $data['experience_years'] ?? null,
                'notes' => $data['notes'] ?? null,
                'password' => $data['password'],
                'status' => CounterApplication::PENDING,
                'ip' => $ip,
            ]);
        });

        $this->sendCode($application);

        return $application;
    }

    public function sendCode(CounterApplication $application): void
    {
        if ($application->isVerified() || ! $application->isPending()) {
            throw ValidationException::withMessages(['code' => 'هذا الطلب لا يحتاج رمز تحقق.']);
        }

        [, $code] = OtpCode::issue($application->phone, OtpCode::PURPOSE_COUNTER_APPLICATION);

        $this->sms->send($application->phone, "رمز توثيق طلب التوظيف في حوات: {$code} — صالح لعشر دقائق.");
    }

    public function verify(CounterApplication $application, string $code): CounterApplication
    {
        if ($application->isVerified()) {
            return $application;
        }

        if (! $application->isPending()) {
            throw ValidationException::withMessages(['code' => 'هذا الطلب لم يعد قيد المراجعة.']);
        }

        $otp = OtpCode::latestFor($application->phone, OtpCode::PURPOSE_COUNTER_APPLICATION);

        if (! $otp || $otp->isExpired()) {
            throw ValidationException::withMessages(['code' => 'انتهت صلاحية الرمز — اطلب رمزًا جديدًا.']);
        }

        if ($otp->isLocked()) {
            throw ValidationException::withMessages(['code' => 'تجاوزت عدد المحاولات — اطلب رمزًا جديدًا.']);
        }

        if (! $otp->attempt($code)) {
            throw ValidationException::withMessages(['code' => 'الرمز غير صحيح.']);
        }

        // طلب آخر موثّق بالجوال نفسه سبقه (قدّم مرتين من جهازين): يبقى الأول.
        $duplicate = CounterApplication::where('phone', $application->phone)
            ->whereKeyNot($application->id)
            ->where('status', CounterApplication::PENDING)
            ->whereNotNull('phone_verified_at')
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['code' => 'لهذا الجوال طلب آخر قيد المراجعة.']);
        }

        $otp->forceFill(['used_at' => now()])->save();
        $application->update(['phone_verified_at' => now()]);

        $this->notifier->counterApplied($application->load('port'));

        return $application;
    }

    public function withdraw(CounterApplication $application): CounterApplication
    {
        if (! $application->isPending()) {
            throw ValidationException::withMessages(['application' => 'لا يُسحب إلا طلب قيد المراجعة.']);
        }

        $application->update(['status' => CounterApplication::WITHDRAWN]);

        return $application;
    }
}
