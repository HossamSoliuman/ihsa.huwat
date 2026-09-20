<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CustomerRequest;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Governorate;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use ResolvesOwnerRecords;

    public function index(Request $request): View
    {
        $rows = Customer::forAccount($request->user())->with(['customerType', 'region', 'governorate'])->withCount('sales')->withSum('sales', 'total')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('panel.owner.customers.index', [
            'rows' => $rows,
            'types' => CustomerType::options(),
            'regions' => Region::orderBy('name')->get(['id', 'name']),
            'governorates' => Governorate::orderBy('name')->get(['id', 'name', 'region_id']),
        ]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated() + ['account_user_id' => $request->user()->id, 'status' => $request->input('status', 'نشط')]);

        return redirect()->route('panel.owner.customers')->with('status', 'تمت إضافة العميل.');
    }

    public function update(CustomerRequest $request, int $id): RedirectResponse
    {
        $this->ownedCustomer($request->user(), $id)->update($request->validated());

        return redirect()->route('panel.owner.customers')->with('status', 'تم تحديث العميل.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $this->ownedCustomer($request->user(), $id)->delete();

        return redirect()->route('panel.owner.customers')->with('status', 'تم حذف العميل.');
    }
}
