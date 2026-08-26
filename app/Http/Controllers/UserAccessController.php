<?php

namespace App\Http\Controllers;

use App\Models\UserPermission;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * المستخدمون والصلاحيات — عرض فقط.
 *
 * إضافة المستخدمين وتعديل أدوارهم يجريان في بوابة المعلومات (تبويب الصلاحيات)،
 * فتبقى هذه الصفحة قراءة موحّدة للأدوار والنطاق الجغرافي دون مسار تحرير ثانٍ.
 */
class UserAccessController extends Controller
{
    /** الأدوار المعتمدة ومسمّياتها العربية. */
    public const ROLES = [
        'top_management' => 'الإدارة العليا',
        'region_manager' => 'مدير المنطقة',
        'governorate_manager' => 'مدير المحافظة',
        'port_manager' => 'مدير الميناء',
        'fisheries_admin' => 'إدارة المصايد',
        'supervision' => 'الرقابة',
        'researcher' => 'باحث',
        'admin' => 'مدير النظام',
        'user' => 'مستخدم',
    ];

    public function index(Request $request): View
    {
        $users = UserPermission::orderBy('user_email')->get();

        $search = trim((string) $request->query('q'));
        $role = $request->query('role');

        $filtered = $users
            ->when($search !== '', fn ($rows) => $rows->filter(fn (UserPermission $user) => str_contains(
                implode(' ', [$user->full_name, $user->user_email, $user->region, $user->governorate, $user->port]),
                $search,
            )))
            ->when($request->filled('role'), fn ($rows) => $rows->where('role', $role))
            ->values();

        return view('users.index', [
            'users' => $filtered,
            'roles' => self::ROLES,
            'stats' => [
                'total' => $users->count(),
                'active' => $users->where('active', true)->count(),
                'admins' => $users->whereIn('role', ['admin', 'top_management'])->count(),
                'roles' => $users->pluck('role')->unique()->count(),
            ],
        ]);
    }
}
