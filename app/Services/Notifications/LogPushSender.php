<?php

namespace App\Services\Notifications;

use App\Models\AppNotification;
use Illuminate\Support\Facades\Log;

/**
 * يكتب الإشعار في السجل بدل دفعه — للتطوير وقبل تهيئة Firebase.
 */
class LogPushSender implements PushSender
{
    public function send(string $fcmToken, AppNotification $notification): bool
    {
        Log::channel(config('logging.default'))->info("[PUSH] إلى {$notification->user_id}: {$notification->title} — {$notification->body}", [
            'fcm_token' => substr($fcmToken, 0, 12).'…',
            'data' => $notification->data,
        ]);

        return false;
    }
}
