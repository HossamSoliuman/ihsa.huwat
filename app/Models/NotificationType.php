<?php

namespace App\Models;

/**
 * قائمة مرجعية — جدول notification_types. المفاتيح الثابتة في
 * App\Services\Notifications\Notifier تُطابق أسماء الصفوف المبذورة.
 */
class NotificationType extends LookupModel
{
    protected $table = 'notification_types';
}
