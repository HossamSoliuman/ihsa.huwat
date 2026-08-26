<?php

namespace App\Http\Controllers;

use App\Models\FisherServiceRequest;
use App\Models\FisherServiceStaff;
use App\Models\SeasonLicense;
use App\Models\SupportTicket;
use App\Support\ServicesSection;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * بوابة قسم الخدمات والتراخيص — مدخل القسم وسجل لوحاته.
 */
class ServicesPortalController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->query('q');

        return view('services.index', [
            'query' => $query,
            'groups' => ServicesSection::search($query),
            'dashboards' => ServicesSection::dashboardCount(),
            'tiles' => [
                'open' => FisherServiceRequest::whereIn('status', FisherServiceRequest::OPEN)->count(),
                'approval' => FisherServiceRequest::where('status', 'بانتظار الاعتماد')->count(),
                'staff' => FisherServiceStaff::where('active', true)->count(),
                'licenses' => SeasonLicense::where('status', 'سارية')->count(),
                'tickets' => SupportTicket::whereNotIn('status', SupportTicket::CLOSED)->count(),
            ],
        ]);
    }
}
