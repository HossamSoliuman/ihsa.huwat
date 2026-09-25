<?php

namespace App\Http\Controllers\Panel\Dalal;

use App\Http\Controllers\Controller;
use App\Models\DalalProfile;
use App\Models\PaymentStatus;
use App\Models\Sale;
use App\Models\Species;
use App\Services\Dalal\DalalAccounts;
use App\Services\Dalal\DalalReports;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * التقارير: اختيار النوع والفلاتر، ثم صفحة جاهزة للطباعة في نافذة جديدة.
 */
class ReportController extends Controller
{
    public function index(Request $request, DalalAccounts $accounts): View
    {
        return view('panel.dalal.reports.index', [
            'types' => DalalReports::TYPES,
            'species' => Species::orderBy('name_ar')->get(['id', 'name_ar']),
            'paymentStatuses' => PaymentStatus::options(),
            'statuses' => [Sale::IN_PROGRESS, Sale::COMPLETED],
            'owners' => $accounts->owners($request->user())->map(fn ($row) => ['id' => $row['id'], 'name' => $row['name']]),
        ]);
    }

    public function show(Request $request, string $type, DalalReports $reports): View
    {
        $dalal = $request->user();

        return view('panel.dalal.reports.show', [
            'report' => $reports->build($dalal, $type, $request->only(['from', 'to', 'status', 'payment_status_id', 'species_id', 'owner_id'])),
            'profile' => DalalProfile::forUser($dalal)->load('user'),
        ]);
    }
}
