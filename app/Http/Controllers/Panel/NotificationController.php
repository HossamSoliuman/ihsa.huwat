<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Concerns\ResolvesCaptainRecords;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * شاشة الإشعارات — لكل حساب تطبيق أيًّا كان دوره. فتح الإشعار يعلّمه مقروءًا
 * ويذهب إلى رحلته إن كان لها صفحة في بوابة هذا الدور.
 */
class NotificationController extends Controller
{
    use ResolvesCaptainRecords;

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('panel.notifications.index', [
            'notifications' => AppNotification::forUser($user)->with(['type', 'trip'])
                ->when($request->query('filter') === 'unread', fn ($q) => $q->unread())
                ->orderByDesc('id')
                ->paginate(25)->withQueryString(),
            'unread' => AppNotification::forUser($user)->unread()->count(),
            'total' => AppNotification::forUser($user)->count(),
        ]);
    }

    public function read(Request $request, int $notification): RedirectResponse
    {
        $model = $this->ownNotification($request->user(), $notification);
        $model->markRead();

        return redirect($this->tripUrl($request->user(), $model) ?? route('panel.notifications'));
    }

    public function readAll(Request $request): RedirectResponse
    {
        AppNotification::forUser($request->user())->unread()->update(['read_at' => now()]);

        return redirect()->route('panel.notifications')->with('status', 'عُلّمت كل الإشعارات مقروءة.');
    }

    /**
     * صفحة الرحلة في بوابة الدور إن كانت له واحدة.
     */
    private function tripUrl(User $user, AppNotification $notification): ?string
    {
        if ($notification->trip_id === null) {
            return null;
        }

        return match ($user->app_role_key) {
            Role::CAPTAIN => route('panel.captain.trips.show', $notification->trip_id),
            Role::OWNER => route('panel.owner.trips.show', $notification->trip_id),
            default => null,
        };
    }
}
