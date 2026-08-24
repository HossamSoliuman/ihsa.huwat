<?php

namespace App\Http\Controllers;

use App\Support\AnnualBulletinService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnualBulletinController extends Controller
{
    public function index(Request $request, AnnualBulletinService $service): View
    {
        $year = $this->year($request);

        return view('annual-bulletin.index', [
            'year' => $year,
            'years' => range(now()->year, now()->year - 7),
            'edition' => $this->edition($year),
            'report' => $service->build($year),
        ]);
    }

    public function print(Request $request, AnnualBulletinService $service): View
    {
        $year = $this->year($request);

        return view('annual-bulletin.print', [
            'year' => $year,
            'edition' => $this->edition($year),
            'report' => $service->build($year),
        ]);
    }

    private function year(Request $request): int
    {
        $year = (int) $request->query('year', now()->year);

        return max(now()->year - 7, min(now()->year, $year ?: now()->year));
    }

    /**
     * نصوص الإصدار الثابتة — كلمة الإدارة والمنهجية ومصادر البيانات.
     *
     * تُقرأ من الإعدادات لا من قاعدة البيانات: النشرة تصدر بنص واحد متفق عليه،
     * وتحريره قرار إداري لا عملية تشغيلية يومية.
     *
     * @return array<string, string>
     */
    private function edition(int $year): array
    {
        return [
            'title' => 'النشرة السنوية للمصايد البحرية',
            'subtitle' => 'في المملكة العربية السعودية',
            'status' => 'مسودة',
            'manager_title' => 'الإدارة المختصة بالمصايد البحرية',
            'management_message' => 'تقدّم هذه النشرة السنوية صورة شاملة عن نشاط المصايد البحرية، اعتمادًا على البيانات التشغيلية والإحصائية المعتمدة في نظام حوات. وتهدف إلى دعم التخطيط وصناعة القرار، وتحسين إدارة الموارد البحرية، ورفع جودة المتابعة والشفافية.',
            'methodology' => 'تُجمَّع المؤشرات آليًا من سجلات الرحلات والمصيد المعتمد والقوارب والموانئ والأسواق والصيد العرضي المسجّلة في نظام حوات، ثم تُجمَّع حسب السنة والمنطقة والميناء والنوع.',
            'sources_note' => 'تعتمد النشرة على البيانات المسجّلة والمعتمدة داخل نظام حوات وقت إنشاء الإصدار للسنة '.$year.'.',
        ];
    }
}
