<?php

namespace App\Providers;

use App\Services\Sms\LogSmsSender;
use App\Services\Sms\SmsSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        /*
         * مرسل الرسائل النصية: إلى أن يُهيّأ مزوّد في إعدادات التكامل (provider
         * = sms) تُكتب الرموز في السجل. حين يُضاف المزوّد يُستبدل هذا الربط.
         */
        $this->app->singleton(SmsSender::class, LogSmsSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /*
         * رموز التحقق تُخنق بالجوال لا بالعنوان وحده: ثلاث رسائل في الدقيقة
         * للجوال الواحد تكفي من أخطأ الرمز، وتمنع إغراق جوال أحدهم بالرسائل.
         */
        RateLimiter::for('otp', fn (Request $request) => [
            Limit::perMinute(3)->by((string) $request->input('phone')),
            Limit::perMinute(10)->by($request->ip()),
        ]);
    }
}
