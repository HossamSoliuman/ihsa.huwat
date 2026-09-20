<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Models\Role;
use App\Models\Trip;
use App\Models\User;
use App\Services\Owner\OwnerDashboard;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * رئيسة لوحة الإدارة — تتفرّع على دور المستخدم.
 *
 * المدير العام يرى حسابات التطبيق بأدوارها وحال الأسطول والرحلات، والمالك
 * يرى مؤشراته ورحلاته النشطة، وبقية الأدوار ترى رئيسة بوابتها حين تُبنى؛
 * إلى ذلك الحين بطاقة تعريف بالحساب.
 */
class HomeController extends Controller
{
    public function index(Request $request, OwnerDashboard $dashboard): View
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return view('panel.home.super-admin', $this->superAdminData());
        }

        if ($user->hasAppRole(Role::OWNER)) {
            return view('panel.owner.home', ['user' => $user] + $dashboard->for($user));
        }

        return view('panel.home.role', ['user' => $user]);
    }

    private function superAdminData(): array
    {
        $roles = Role::withCount('users')->orderBy('display_order')->get();

        return [
            'roles' => $roles,
            'stats' => [
                'accounts' => $roles->sum('users_count'),
                'active' => User::whereNotNull('role_id')->where('active', true)->count(),
                'boats' => Boat::count(),
                'at_sea' => Trip::where('status', Trip::AT_SEA)->count(),
                'awaiting_count' => Trip::whereIn('status', [Trip::AWAITING_COUNT, Trip::COUNTING])->count(),
            ],
            'recent' => User::with('appRole')->whereNotNull('role_id')->latest()->limit(8)->get(),
        ];
    }
}
