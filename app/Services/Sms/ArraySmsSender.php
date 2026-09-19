<?php

namespace App\Services\Sms;

/**
 * يحتفظ بالرسائل في الذاكرة — للاختبارات: يُقرأ منه الرمز المُرسل.
 */
class ArraySmsSender implements SmsSender
{
    /** @var array<int, array{phone: string, message: string}> */
    public array $sent = [];

    public function send(string $phone, string $message): void
    {
        $this->sent[] = ['phone' => $phone, 'message' => $message];
    }

    public function lastTo(string $phone): ?string
    {
        foreach (array_reverse($this->sent) as $entry) {
            if ($entry['phone'] === $phone) {
                return $entry['message'];
            }
        }

        return null;
    }
}
