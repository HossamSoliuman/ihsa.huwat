<?php

namespace App\Http\Controllers;

use App\Models\FisherServiceStaff;
use App\Models\SupportTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * الدعم الفني — تذاكر المستخدمين الداخليين من التقديم إلى الإغلاق.
 *
 * التذكرة لا تُغلق بلا حلّ مكتوب: قيمة السجل في أنه يشرح بماذا انتهت المشكلة،
 * لا في أن حالتها تغيّرت.
 */
class SupportTicketController extends Controller
{
    /** الوحدات التي قد تُنسب إليها التذكرة — أقسام اللوحة كما يراها المستخدم. */
    public const MODULES = [
        'خدمات الصيادين', 'رخص المواسم', 'الرقابة والامتثال', 'الإحصاء الميداني',
        'التقارير والنشرات', 'الهيكل التنظيمي والصلاحيات', 'مركز الإدارة', 'أخرى',
    ];

    public function index(Request $request): View
    {
        $tickets = SupportTicket::with('assignedStaff')->orderByDesc('submitted_at')->orderByDesc('id')->get();

        $status = $request->query('status');
        $category = $request->query('category');

        $filtered = $tickets
            ->when($request->filled('status'), fn ($rows) => $rows->where('status', $status))
            ->when($request->filled('category'), fn ($rows) => $rows->where('category', $category))
            ->values();

        return view('support.index', [
            'tickets' => $filtered,
            'categories' => SupportTicket::CATEGORIES,
            'statuses' => SupportTicket::STATUSES,
            'priorities' => SupportTicket::PRIORITIES,
            'modules' => self::MODULES,
            'nextNumber' => SupportTicket::nextNumber(),
            'staff' => FisherServiceStaff::where('active', true)->orderBy('name')->get(),
            'stats' => [
                'total' => $tickets->count(),
                'open' => $tickets->filter(fn (SupportTicket $ticket) => $ticket->isOpen())->count(),
                'urgent' => $tickets->where('priority', 'عاجلة')->filter(fn (SupportTicket $ticket) => $ticket->isOpen())->count(),
                'unassigned' => $tickets->whereNull('assigned_staff_id')->filter(fn (SupportTicket $ticket) => $ticket->isOpen())->count(),
                'resolved' => $tickets->whereIn('status', SupportTicket::CLOSED)->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.implode(',', SupportTicket::CATEGORIES)],
            'priority' => ['required', 'in:'.implode(',', SupportTicket::PRIORITIES)],
            'module' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'submitted_by_name' => ['nullable', 'string', 'max:255'],
            'submitted_by_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
        ]);

        SupportTicket::create($data + [
            'ticket_number' => SupportTicket::nextNumber(),
            'status' => 'جديدة',
            'submitted_at' => now(),
        ]);

        return $this->back($request, 'تم استلام طلبك — سيصلك رد خلال ٢٤ ساعة عمل');
    }

    public function assign(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'assigned_staff_id' => ['required', 'exists:fisher_service_staff,id'],
        ]);

        $ticket->update($data + [
            'assigned_at' => now(),
            'status' => $ticket->status === 'جديدة' ? 'قيد المعالجة' : $ticket->status,
        ]);

        return $this->back($request, 'تم إسناد التذكرة');
    }

    public function resolve(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', SupportTicket::STATUSES)],
            'resolution' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        // الإغلاق بلا حلّ مكتوب يفقد السجل قيمته، فيُرَدّ النموذج.
        if (in_array($data['status'], SupportTicket::CLOSED, true) && trim((string) ($data['resolution'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'resolution' => 'اكتب الحل أو الرد قبل إغلاق التذكرة.',
            ]);
        }

        $ticket->update($data + [
            'resolved_at' => in_array($data['status'], SupportTicket::CLOSED, true) ? now() : null,
        ]);

        return $this->back($request, 'تم تحديث التذكرة');
    }

    private function back(Request $request, string $message): RedirectResponse
    {
        return redirect()
            ->route('services.support', $request->only('status', 'category'))
            ->with('status', $message);
    }
}
