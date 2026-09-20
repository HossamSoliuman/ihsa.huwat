<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\EmployeeRequest;
use App\Models\JobTitle;
use App\Models\OwnerEmployee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    use ResolvesOwnerRecords;

    public function index(Request $request): View
    {
        $rows = OwnerEmployee::forOwner($request->user())->with(['jobTitle'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('panel.owner.employees.index', [
            'rows' => $rows,
            'jobTitles' => JobTitle::options(),
        ]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        OwnerEmployee::create($request->validated() + ['owner_id' => $request->user()->id, 'status' => $request->input('status', 'نشط')]);

        return redirect()->route('panel.owner.employees')->with('status', 'تمت إضافة الموظف.');
    }

    public function update(EmployeeRequest $request, int $id): RedirectResponse
    {
        $this->ownedEmployee($request->user(), $id)->update($request->validated());

        return redirect()->route('panel.owner.employees')->with('status', 'تم تحديث الموظف.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $this->ownedEmployee($request->user(), $id)->delete();

        return redirect()->route('panel.owner.employees')->with('status', 'تم حذف الموظف.');
    }
}
