<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\PayrollLineRequest;
use App\Http\Requests\Owner\PayrollPaymentRequest;
use App\Http\Requests\Owner\PayrollRequest;
use App\Models\Boat;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Payroll;
use App\Services\Owner\ExpenseService;
use App\Services\Owner\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * مسيرات الرواتب: مسير لكل قارب وشهر، سطر لكل فرد، سداد فردي أو كلي،
 * وطباعة المسير.
 */
class PayrollController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly PayrollService $payrolls) {}

    public function index(Request $request): View
    {
        $owner = $request->user();

        $query = Payroll::forOwner($owner)
            ->when($request->filled('boat'), fn ($q) => $q->where('boat_id', $request->query('boat')))
            ->when($request->filled('year'), fn ($q) => $q->where('year', $request->query('year')))
            ->when($request->filled('status'), fn ($q) => $q->where('payment_status_id', $request->query('status')));

        // المسيرات المفتوحة تُحدَّث قبل العرض فتطابق أرقامها ما سُجّل بعدها.
        (clone $query)->where('payment_status_id', '!=', PaymentStatus::named(ExpenseService::PAID)->id)->get()
            ->each(fn (Payroll $payroll) => $this->payrolls->refresh($payroll, $owner));

        $rows = (clone $query)->with(['paymentStatus', 'lines'])->orderByDesc('year')->orderByDesc('month')->orderBy('boat_name')->get();

        return view('panel.owner.payrolls.index', [
            'rows' => $rows,
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'years' => Payroll::forOwner($owner)->distinct()->orderByDesc('year')->pluck('year')->push(now()->year)->unique()->sortDesc()->values(),
            'statuses' => PaymentStatus::options(),
        ]);
    }

    public function store(PayrollRequest $request): RedirectResponse
    {
        $owner = $request->user();
        [$year, $month] = array_map('intval', explode('-', $request->validated('period')));
        $boat = $this->ownedBoat($owner, $request->validated('boat_id'));

        $existing = Payroll::forOwner($owner)->where('boat_id', $boat->id)->where('year', $year)->where('month', $month)->first();

        if ($existing) {
            return redirect()->route('panel.owner.payrolls.show', $existing->id)->with('status', 'مسير هذا الشهر موجود — فُتح للمراجعة.');
        }

        $payroll = $this->payrolls->generate($owner, $boat, $year, $month);

        return redirect()->route('panel.owner.payrolls.show', $payroll->id)->with('status', "تم إنشاء المسير {$payroll->payroll_number}.");
    }

    public function show(Request $request, int $payroll): View
    {
        $record = $this->payrolls->refresh($this->ownedPayroll($request->user(), $payroll), $request->user());

        return view('panel.owner.payrolls.show', [
            'payroll' => $record->load(['lines.payType', 'lines.paymentMethod', 'lines.fisher', 'paymentStatus', 'expense']),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function print(Request $request, int $payroll): View
    {
        $record = $this->payrolls->refresh($this->ownedPayroll($request->user(), $payroll), $request->user());

        return view('panel.owner.payrolls.print', [
            'owner' => $request->user(),
            'payroll' => $record->load(['lines.payType', 'lines.paymentMethod', 'paymentStatus']),
        ]);
    }

    public function updateLine(PayrollLineRequest $request, int $payroll, int $line): RedirectResponse
    {
        $record = $this->ownedPayroll($request->user(), $payroll);
        $row = $this->payrolls->updateLine($this->ownedPayrollLine($record, $line), $request->validated(), $request->user());

        return redirect()->route('panel.owner.payrolls.show', $record->id)->with('status', "تم تحديث سطر {$row->member_name}.");
    }

    public function payLine(PayrollPaymentRequest $request, int $payroll, int $line): RedirectResponse
    {
        $record = $this->ownedPayroll($request->user(), $payroll);
        $row = $this->payrolls->payLine($this->ownedPayrollLine($record, $line), $request->validated('payment_method_id'), $request->user());

        return redirect()->route('panel.owner.payrolls.show', $record->id)->with('status', "تم سداد {$row->member_name}: ".number_format($row->paid_amount, 2).' ر.س.');
    }

    public function payAll(PayrollPaymentRequest $request, int $payroll): RedirectResponse
    {
        $record = $this->ownedPayroll($request->user(), $payroll);
        $count = $this->payrolls->payAll($record, $request->validated('payment_method_id'), $request->user());

        return redirect()->route('panel.owner.payrolls.show', $record->id)->with('status', "تم سداد {$count} من أفراد المسير.");
    }

    public function destroy(Request $request, int $payroll): RedirectResponse
    {
        $this->payrolls->delete($this->ownedPayroll($request->user(), $payroll), $request->user());

        return redirect()->route('panel.owner.payrolls')->with('status', 'تم حذف المسير.');
    }
}
