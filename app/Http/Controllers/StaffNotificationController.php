<?php

namespace App\Http\Controllers;

use App\Models\StaffNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * التنبيهات الإدارية — ما يصل الموظف من طلبات وما ينتظر اعتماده.
 */
class StaffNotificationController extends Controller
{
    public const TYPES = ['طلب جديد', 'بانتظار الاعتماد', 'تذكير', 'أخرى'];

    public function index(Request $request): View
    {
        $notifications = StaffNotification::with('staff')->latest('created_at')->get();

        // "غير مقروءة" هو المعروض أولًا: التنبيه يُقرأ ليُعمل به لا ليُؤرشف.
        $read = $request->query('read', 'unread');
        $type = $request->query('type');

        $filtered = $notifications
            ->when($read === 'unread', fn ($rows) => $rows->where('read', false))
            ->when($read === 'read', fn ($rows) => $rows->where('read', true))
            ->when($request->filled('type'), fn ($rows) => $rows->where('notification_type', $type))
            ->values();

        return view('staff-notifications.index', [
            'notifications' => $filtered,
            'read' => $read,
            'types' => self::TYPES,
            'stats' => [
                'total' => $notifications->count(),
                'unread' => $notifications->where('read', false)->count(),
                'approval' => $notifications->where('notification_type', 'بانتظار الاعتماد')->count(),
                'urgent' => $notifications->where('priority', 'عاجلة')->where('read', false)->count(),
            ],
        ]);
    }

    public function markRead(Request $request, StaffNotification $notification): RedirectResponse
    {
        $notification->update(['read' => true, 'read_at' => now()]);

        return $this->back($request, 'تم تعليم التنبيه كمقروء');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $count = StaffNotification::where('read', false)->update(['read' => true, 'read_at' => now()]);

        return $this->back($request, "تم تعليم {$count} تنبيه كمقروء");
    }

    private function back(Request $request, string $message): RedirectResponse
    {
        return redirect()
            ->route('subadmin.staff-notifications', $request->only('read', 'type'))
            ->with('status', $message);
    }
}
