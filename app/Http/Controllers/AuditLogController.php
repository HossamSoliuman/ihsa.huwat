<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * سجل العمليات — قراءة التدقيق كاملًا مع تصفيته بالإجراء والنص.
 *
 * السجل لا يُحرَّر ولا يُحذف من هنا: قيمته في أنه لا يتغيّر بعد الكتابة.
 */
class AuditLogController extends Controller
{
    /** الإجراءات المعتمدة — ترتيبها ثابت حتى لا تتبدّل قائمة التصفية مع البيانات. */
    public const ACTIONS = ['إنشاء', 'تعديل', 'حذف', 'اعتماد', 'تسجيل دخول', 'تصدير'];

    public function index(Request $request): View
    {
        $logs = AuditLog::latest('created_at')->take(500)->get();

        $search = trim((string) $request->query('q'));
        $action = $request->query('action');

        $filtered = $logs
            ->when($search !== '', fn ($rows) => $rows->filter(fn (AuditLog $log) => str_contains(
                implode(' ', [$log->user_email, $log->role, $log->entity, $log->record_label, $log->details]),
                $search,
            )))
            ->when($request->filled('action'), fn ($rows) => $rows->where('action', $action))
            ->values();

        return view('audit-log.index', [
            'logs' => $filtered,
            'actions' => collect(self::ACTIONS)->merge($logs->pluck('action'))->unique()->values(),
            'stats' => [
                'total' => $logs->count(),
                'create' => $logs->where('action', 'إنشاء')->count(),
                'edit' => $logs->where('action', 'تعديل')->count(),
                'delete' => $logs->where('action', 'حذف')->count(),
                'approve' => $logs->where('action', 'اعتماد')->count(),
            ],
        ]);
    }
}
