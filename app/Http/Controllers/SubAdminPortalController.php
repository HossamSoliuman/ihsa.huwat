<?php

namespace App\Http\Controllers;

use App\Models\AdminTask;
use App\Models\Alert;
use App\Models\OrgPosition;
use App\Models\StaffNotification;
use App\Support\AdminSection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubAdminPortalController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->query('q');

        return view('sub-admin.index', [
            'query' => $query,
            'groups' => AdminSection::search($query),
            'dashboards' => AdminSection::dashboardCount(),
            'groupCount' => count(AdminSection::groups()),
            'tiles' => [
                'positions' => OrgPosition::count(),
                'openTasks' => AdminTask::whereNotIn('status', AdminTask::CLOSED)->count(),
                'unread' => StaffNotification::where('read', false)->count(),
                'alerts' => Alert::where('status', '!=', 'تم الحل')->count(),
            ],
        ]);
    }
}
