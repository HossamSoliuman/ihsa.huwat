<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * يكتب الرسالة في السجل بدل إرسالها — للتطوير وقبل تهيئة مزوّد الرسائل.
 */
class LogSmsSender implements SmsSender
{
    public function send(string $phone, string $message): void
    {
        Log::channel(config('logging.default'))->info("[SMS] إلى {$phone}: {$message}");
    }
}
