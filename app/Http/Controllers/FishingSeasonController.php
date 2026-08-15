<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FishingSeason;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FishingSeasonController extends Controller
{
    public function index(Request $request): View
    {
        $seasons = FishingSeason::orderByDesc('start_month')->get();

        $filtered = $seasons
            ->when($request->filled('sea'), fn ($c) => $c->where('sea', $request->query('sea')))
            ->when($request->filled('now'), fn ($c) => $c->filter(fn ($s) => ($s->isOpenNow() ? 'مفتوح' : 'مغلق') === $request->query('now')))
            ->when($request->filled('approval'), fn ($c) => $c->where('approval_status', $request->query('approval')))
            ->values();

        $stats = [
            'open' => $seasons->filter(fn ($s) => $s->isOpenNow() && $s->approval_status === 'منشور')->count(),
            'total' => $seasons->count(),
            'pending' => $seasons->where('approval_status', 'قيد المراجعة')->count(),
            'issued' => $seasons->sum('licenses_issued'),
            'active' => $seasons->sum('licenses_active'),
            'max' => $seasons->sum('licenses_max'),
        ];

        return view('fishing-seasons.index', [
            'seasons' => $filtered,
            'stats' => $stats,
            'months' => FishingSeason::MONTHS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $season = FishingSeason::create($this->validated($request));
        $this->audit('إنشاء', $season);

        return redirect()->route('fishing-seasons')->with('status', 'تم حفظ الموسم بنجاح');
    }

    public function update(Request $request, FishingSeason $fishingSeason): RedirectResponse
    {
        $fishingSeason->update($this->validated($request));
        $this->audit('تعديل', $fishingSeason);

        return redirect()->route('fishing-seasons')->with('status', 'تم تحديث الموسم بنجاح');
    }

    public function updateStatus(Request $request, FishingSeason $fishingSeason): RedirectResponse
    {
        $next = $request->validate(['approval_status' => ['required', 'in:قيد المراجعة,معتمد,منشور,مرفوض']])['approval_status'];

        $data = ['approval_status' => $next];
        if ($next === 'منشور') {
            $data['status'] = $fishingSeason->isOpenNow() ? 'مفتوح' : 'مغلق';
        }
        $fishingSeason->update($data);
        $this->audit('اعتماد', $fishingSeason, "تغيير حالة الموسم إلى {$next}");

        return redirect()->route('fishing-seasons')->with('status', "تم تحديث حالة الموسم إلى {$next}");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'species' => ['required', 'string', 'max:255'],
            'sea' => ['required', 'in:البحر الأحمر,الخليج العربي'],
            'region' => ['nullable', 'string', 'max:255'],
            'start_month' => ['required', 'integer', 'between:1,12'],
            'end_month' => ['required', 'integer', 'between:1,12'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'ban_start_date' => ['nullable', 'date'],
            'ban_end_date' => ['nullable', 'date'],
            'licenses_max' => ['nullable', 'integer', 'min:0'],
            'licenses_issued' => ['nullable', 'integer', 'min:0'],
            'licenses_active' => ['nullable', 'integer', 'min:0'],
            'boats_count' => ['nullable', 'integer', 'min:0'],
            'license_type' => ['nullable', 'string', 'max:255'],
            'gear_type' => ['nullable', 'string', 'max:255'],
            'min_size_cm' => ['nullable', 'integer', 'min:0'],
            'allowed_areas' => ['nullable', 'string'],
            'prohibited_areas' => ['nullable', 'string'],
            'authority' => ['nullable', 'string', 'max:255'],
            'decision_number' => ['nullable', 'string', 'max:100'],
            'decision_date' => ['nullable', 'date'],
            'decision_document_url' => ['nullable', 'string', 'max:500'],
            'approval_status' => ['nullable', 'in:مسودة,قيد المراجعة,معتمد,منشور,مرفوض'],
            'status' => ['nullable', 'in:مفتوح,مغلق,موقوف مؤقتاً'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function audit(string $action, FishingSeason $season, ?string $details = null): void
    {
        AuditLog::create([
            'action' => $action,
            'entity' => 'FishingSeason',
            'record_label' => $season->name,
            'details' => $details ?? "{$action}: {$season->name}",
        ]);
    }
}