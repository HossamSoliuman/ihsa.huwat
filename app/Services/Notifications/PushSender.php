<?php

namespace App\Services\Notifications;

use App\Models\AppNotification;

/**
 * دافع إشعارات الجوال (Firebase Cloud Messaging).
 *
 * التنفيذ يُختار في AppServiceProvider من إعدادات التكامل (provider =
 * firebase): ما دام التكامل معطّلًا أو بلا حساب خدمة يُكتب الإشعار في السجل
 * بدل دفعه. الإشعار محفوظ في الجدول قبل الدفع في الحالين، فشاشة الإشعارات
 * لا تعتمد على وصول الدفعة.
 */
interface PushSender
{
    /**
     * يدفع الإشعار إلى رمز جهاز واحد ويعيد true إن قُبل.
     */
    public function send(string $fcmToken, AppNotification $notification): bool;
}
