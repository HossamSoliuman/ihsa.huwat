<?php

namespace App\Services\Notifications;

use App\Models\AppNotification;
use App\Models\NotificationType;
use App\Models\Trip;
use App\Models\User;
use Throwable;

/**
 * يكتب إشعار التطبيق ويدفعه إلى جوال صاحبه.
 *
 * المصدر الوحيد لإشعارات الرحلة: TripService يستدعيه عند كل انتقال في
 * الدورة، فيصل الكابتن ما يخصّه والمالك ما يفعله كابتنه والعدّاد. الثوابت
 * هنا أسماء صفوف notification_types المبذورة (كما StockLedger مع أنواع
 * الحركات).
 */
class Notifier
{
    public const TRIP_ASSIGNED = 'رحلة جديدة بانتظارك';

    public const TRIP_STARTED = 'الرحلة قيد التنفيذ';

    public const TRIP_CANCELLED = 'تم إلغاء الرحلة';

    public const TRIP_COMPLETED = 'الرحلة مكتملة';

    public const CATCH_SUBMITTED = 'تم إرسال المخرجات';

    public const COUNT_COMPLETED = 'اكتمل العد';

    public function __construct(private readonly PushSender $push) {}

    /**
     * يحفظ الإشعار ثم يدفعه إن كان للحساب رمز جهاز. فشل الدفع لا يُفشل
     * العملية التي أنتجت الإشعار — يبقى في الجدول ويُقرأ من الشاشة.
     */
    public function notify(?User $to, string $type, string $title, string $body, ?Trip $trip = null, array $data = []): ?AppNotification
    {
        if ($to === null) {
            return null;
        }

        $notification = AppNotification::create([
            'user_id' => $to->id,
            'notification_type_id' => NotificationType::named($type)->id,
            'trip_id' => $trip?->id,
            'title' => $title,
            'body' => $body,
            'data' => array_filter([
                'type' => $type,
                'trip_id' => $trip?->id,
                'trip_number' => $trip?->trip_number,
                'status' => $trip?->status,
            ] + $data, fn ($v) => $v !== null),
        ]);

        if ($to->fcm_token) {
            try {
                if ($this->push->send($to->fcm_token, $notification)) {
                    $notification->update(['pushed_at' => now()]);
                }
            } catch (Throwable) {
                // الدفع تحسين لا شرط: الإشعار محفوظ ويُعرض في الشاشة.
            }
        }

        return $notification;
    }

    /*
     * إشعارات دورة الرحلة — النصوص كما تظهر في شاشة الإشعارات في التطبيق.
     */

    public function tripAssigned(Trip $trip): void
    {
        $this->notify($trip->captain, self::TRIP_ASSIGNED, 'رحلة جديدة بانتظارك',
            "أُسندت إليك الرحلة {$trip->trip_number} على القارب ".($trip->boat?->name ?? '').' — ابدأها من شاشة الرحلات.', $trip);
    }

    /**
     * من بدأ الرحلة لا يُبلَّغ بها: إن بدأها الكابتن يُبلَّغ المالك، وإن بدأها
     * المالك نيابةً عنه يُبلَّغ الكابتن.
     */
    public function tripStarted(Trip $trip, ?User $by): void
    {
        $this->notify($this->otherParty($trip, $by), self::TRIP_STARTED, 'الرحلة قيد التنفيذ',
            "انطلقت الرحلة {$trip->trip_number} — القارب ".($trip->boat?->name ?? '').' في البحر الآن.', $trip);
    }

    public function tripCancelled(Trip $trip, ?User $by): void
    {
        $this->notify($this->otherParty($trip, $by), self::TRIP_CANCELLED, 'تم إلغاء الرحلة',
            "أُلغيت الرحلة {$trip->trip_number}: {$trip->cancel_reason}", $trip);
    }

    public function catchSubmitted(Trip $trip, ?User $by): void
    {
        $this->notify($this->otherParty($trip, $by), self::CATCH_SUBMITTED, 'تم إرسال المخرجات',
            "أُرسلت مخرجات الرحلة {$trip->trip_number}: ".number_format((float) $trip->captain_input_kg, 1).' كجم — بانتظار العدّاد.', $trip);
    }

    /**
     * اكتمال العد يُنهي الرحلة عند الكابتن ويفتح البيع عند المالك.
     */
    public function countCompleted(Trip $trip): void
    {
        $kg = number_format((float) $trip->actual_weight_kg, 1);

        $this->notify($trip->captain, self::TRIP_COMPLETED, 'الرحلة مكتملة',
            "اكتمل عدّ مصيد الرحلة {$trip->trip_number}: {$kg} كجم.", $trip);

        $this->notify($trip->owner, self::COUNT_COMPLETED, 'اكتمل العد',
            "عُدّ مصيد الرحلة {$trip->trip_number} ({$kg} كجم) وصار جاهزًا للبيع.", $trip);
    }

    /**
     * الطرف الآخر في الرحلة: المالك إن كان الفاعل الكابتن، والكابتن إن كان
     * الفاعل المالك أو الوزارة.
     */
    private function otherParty(Trip $trip, ?User $by): ?User
    {
        if ($by !== null && $trip->captain_id !== null && $by->id === $trip->captain_id) {
            return $trip->owner;
        }

        return $trip->captain;
    }
}
