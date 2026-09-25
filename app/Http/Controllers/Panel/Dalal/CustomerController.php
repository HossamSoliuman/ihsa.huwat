<?php

namespace App\Http\Controllers\Panel\Dalal;

use App\Http\Controllers\Concerns\ResolvesDalalRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dalal\CustomerRequest;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Governorate;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * عملاء الدلال — قائمته المستقلة عن عملاء الملاك (customers.account_user_id).
 */
class CustomerController extends Controller
{
    use ResolvesDalalRecords;

    public function index(Request $request): View
    {
        $rows = Customer::forAccount($request->user())->with(['customerType', 'region', 'governorate'])->withCount('sales')->withSum('sales', 'total')
            ->when($request->filled('region_id'), fn ($q) => $q->where('region_id', $request->query('region_id')))
            ->when($request->filled('governorate_id'), fn ($q) => $q->where('governorate_id', $request->query('governorate_id')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')
                ->orWhere('email', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('panel.dalal.customers.index', [
            'rows' => $rows,
            'types' => CustomerType::options(),
            'regions' => Region::orderBy('name')->get(['id', 'name']),
            'governorates' => Governorate::orderBy('name')->get(['id', 'name', 'region_id']),
        ]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated() + ['account_user_id' => $request->user()->id, 'status' => $request->input('status', 'نشط')]);

        return redirect()->route('panel.dalal.customers')->with('status', 'تمت إضافة العميل.');
    }

    public function update(CustomerRequest $request, int $id): RedirectResponse
    {
        $this->dalalCustomer($request->user(), $id)->update($request->validated());

        return redirect()->route('panel.dalal.customers')->with('status', 'تم تحديث العميل.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $this->dalalCustomer($request->user(), $id)->delete();

        return redirect()->route('panel.dalal.customers')->with('status', 'تم حذف العميل.');
    }
}
