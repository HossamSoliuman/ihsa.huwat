<?php

namespace App\Services\Sms;

/**
 * مرسل الرسائل النصية — رموز التحقق وما يليها من تنبيهات.
 *
 * التنفيذ يُختار في AppServiceProvider من إعدادات التكامل (provider = sms):
 * ما دام التكامل معطّلًا أو بلا مفاتيح تُكتب الرسالة في السجل بدل إرسالها.
 */
interface SmsSender
{
    public function send(string $phone, string $message): void;
}
