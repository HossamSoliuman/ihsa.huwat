<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ExpenseRequest;
use App\Models\Boat;
use App\Models\Expense;
use App\Models\ExpenseGroup;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Owner\ExpenseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly ExpenseService $expenses) {}

    public function index(Request $request): View
    {
        $owner = $request->user();
        $query = $this->filtered($request, $owner);

        $totals = (clone $query)->toBase()
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(total), 0) as total, COALESCE(SUM(paid_amount), 0) as paid, COALESCE(SUM(vat_amount), 0) as vat')
            ->first();

        $byGroup = (clone $query)->toBase()
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->join('expense_groups', 'expense_groups.id', '=', 'expense_categories.expense_group_id')
            ->groupBy('expense_groups.id', 'expense_groups.name')
            ->orderByDesc(DB::raw('SUM(expenses.total)'))
            ->get(['expense_groups.name', DB::raw('SUM(expenses.total) as total')]);

        return view('panel.owner.expenses.index', [
            'rows' => $query->with(['category.group', 'boat', 'trip', 'vendor', 'paymentStatus', 'paymentMethod', 'source'])
                ->orderByDesc('date')->orderByDesc('id')
                ->paginate(25)->withQueryString(),
            'totals' => $totals,
            'byGroup' => $byGroup,
        ] + $this->formOptions($owner));
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $expense = $this->expenses->create($request->user(), $request->validated(), $request->file('attachment'));

        return redirect()->route('panel.owner.expenses')->with('status', "تم تسجيل المصروف {$expense->expense_number}.");
    }

    public function update(ExpenseRequest $request, int $expense): RedirectResponse
    {
        $row = $this->ownedExpense($request->user(), $expense);
        $this->expenses->update($request->user(), $row, $request->validated(), $request->file('attachment'), $request->boolean('remove_attachment'));

        return back()->with('status', "تم تحديث المصروف {$row->expense_number}.");
    }

    public function destroy(Request $request, int $expense): RedirectResponse
    {
        $row = $this->ownedExpense($request->user(), $expense);
        $this->expenses->delete($request->user(), $row);

        return redirect()->route('panel.owner.expenses')->with('status', "تم حذف المصروف {$row->expense_number}.");
    }

    public function payment(Request $request, int $expense): RedirectResponse
    {
        $row = $this->ownedExpense($request->user(), $expense);
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0.01']], [], ['amount' => 'المبلغ']);

        $this->expenses->recordPayment($request->user(), $row, (float) $data['amount']);

        return back()->with('status', "تم تسجيل السداد على المصروف {$row->expense_number}.");
    }

    /**
     * سند المصروف A4 للطباعة أو الحفظ PDF.
     */
    public function print(Request $request, int $expense): View
    {
        return view('panel.owner.expenses.print', [
            'expense' => $this->ownedExpense($request->user(), $expense)
                ->load(['category.group', 'boat', 'trip', 'vendor', 'paymentStatus', 'paymentMethod', 'owner']),
        ]);
    }

    /**
     * كشف المصروفات بالتصفية نفسها المعروضة في القائمة.
     */
    public function report(Request $request): View
    {
        $owner = $request->user();
        $rows = $this->filtered($request, $owner)
            ->with(['category.group', 'boat', 'trip', 'vendor', 'paymentStatus'])
            ->orderBy('date')->orderBy('id')
            ->get();

        $options = $this->formOptions($owner);

        return view('panel.owner.expenses.report', [
            'owner' => $owner,
            'rows' => $rows,
            'filters' => array_filter([
                'الفترة' => $request->filled('from') || $request->filled('to')
                    ? ($request->query('from') ?: '…').' — '.($request->query('to') ?: '…') : null,
                'القارب' => $options['boats']->firstWhere('id', (int) $request->query('boat'))?->name,
                'المجموعة' => $options['groups']->firstWhere('id', (int) $request->query('group'))?->name,
                'الفئة' => $options['groups']->flatMap->categories->firstWhere('id', (int) $request->query('category'))?->name,
                'حالة الدفع' => $options['statuses']->firstWhere('id', (int) $request->query('status'))?->name,
                'بحث' => $request->query('search'),
            ]),
        ]);
    }

    private function filtered(Request $request, User $owner): Builder
    {
        return Expense::forOwner($owner)
            ->when($request->filled('boat'), fn ($q) => $q->where('boat_id', $request->query('boat')))
            ->when($request->filled('trip'), fn ($q) => $q->where('trip_id', $request->query('trip')))
            ->when($request->filled('category'), fn ($q) => $q->where('expense_category_id', $request->query('category')))
            ->when($request->filled('group'), fn ($q) => $q->whereHas('category', fn ($c) => $c->where('expense_group_id', $request->query('group'))))
            ->when($request->filled('status'), fn ($q) => $q->where('payment_status_id', $request->query('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('date', '>=', $request->query('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('date', '<=', $request->query('to')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.$request->query('search').'%';
                $q->where(fn ($s) => $s->where('expense_number', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('notes', 'like', $term));
            });
    }

    private function formOptions(User $owner): array
    {
        return [
            'groups' => ExpenseGroup::withCategories(),
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'trips' => Trip::forOwner($owner)->orderByDesc('id')->limit(200)->get(['id', 'trip_number', 'boat_id']),
            'vendors' => Vendor::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'methods' => PaymentMethod::options(),
            'statuses' => PaymentStatus::options(),
            'vatRate' => (float) config('hawat.vat_rate'),
        ];
    }
}
