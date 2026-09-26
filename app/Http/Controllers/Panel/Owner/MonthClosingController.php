<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\MonthClosingRequest;
use App\Models\MonthClosing;
use App\Services\Owner\MonthClosingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * إغلاق الشهر: القائمة، معاينة الشهر التالي، الإغلاق، العرض والطباعة،
 * وإعادة فتح آخر شهر مُغلق.
 */
class MonthClosingController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly MonthClosingService $closings) {}

    public function index(Request $request): View
    {
        $owner = $request->user();
        $rows = MonthClosing::forOwner($owner)->with('boats')->latestFirst()->get();

        return view('panel.owner.month-closings.index', [
            'rows' => $rows,
            'next' => $this->closings->nextPeriod($owner),
            'isFirst' => $rows->isEmpty(),
        ]);
    }

    public function preview(MonthClosingRequest $request): View
    {
        $owner = $request->user();
        [$year, $month] = $request->period();
        $this->closings->assertClosable($owner, $year, $month);

        return view('panel.owner.month-closings.preview', [
            'data' => $this->closings->preview($owner, $year, $month),
            'period' => $request->validated('period'),
        ]);
    }

    public function store(MonthClosingRequest $request): RedirectResponse
    {
        [$year, $month] = $request->period();
        $closing = $this->closings->close($request->user(), $year, $month, $request->validated('notes'));

        return redirect()->route('panel.owner.month-closings.show', $closing->id)
            ->with('status', "تم إغلاق {$closing->period_label} — مصروفاته ومسيراته مقفلة الآن.");
    }

    public function show(Request $request, int $closing): View
    {
        $record = $this->ownedMonthClosing($request->user(), $closing);

        return view('panel.owner.month-closings.show', [
            'closing' => $record->load('closedBy'),
            'data' => $this->closings->present($record),
            'isLatest' => MonthClosing::forOwner($request->user())->latestFirst()->value('id') === $record->id,
        ]);
    }

    public function print(Request $request, int $closing): View
    {
        $record = $this->ownedMonthClosing($request->user(), $closing);

        return view('panel.owner.month-closings.print', [
            'owner' => $request->user(),
            'closing' => $record,
            'data' => $this->closings->present($record),
        ]);
    }

    public function reopen(Request $request, int $closing): RedirectResponse
    {
        $record = $this->ownedMonthClosing($request->user(), $closing);
        $this->closings->reopen($record, $request->user());

        return redirect()->route('panel.owner.month-closings')->with('status', "أُعيد فتح {$record->period_label} — أرقامه ومسيراته تُحسب من جديد.");
    }
}
