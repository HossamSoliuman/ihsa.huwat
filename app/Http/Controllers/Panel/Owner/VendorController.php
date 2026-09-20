<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\VendorRequest;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    use ResolvesOwnerRecords;

    public function index(Request $request): View
    {
        $rows = Vendor::forOwner($request->user())->with([])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('panel.owner.vendors.index', [
            'rows' => $rows,
        ]);
    }

    public function store(VendorRequest $request): RedirectResponse
    {
        Vendor::create($request->validated() + ['owner_id' => $request->user()->id, 'status' => $request->input('status', 'نشط')]);

        return redirect()->route('panel.owner.vendors')->with('status', 'تمت إضافة المورد.');
    }

    public function update(VendorRequest $request, int $id): RedirectResponse
    {
        $this->ownedVendor($request->user(), $id)->update($request->validated());

        return redirect()->route('panel.owner.vendors')->with('status', 'تم تحديث المورد.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $this->ownedVendor($request->user(), $id)->delete();

        return redirect()->route('panel.owner.vendors')->with('status', 'تم حذف المورد.');
    }
}
