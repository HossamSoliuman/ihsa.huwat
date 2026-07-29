<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildAttendanceDashboardAction;
use App\Actions\RecordAttendanceAction;
use App\Actions\StoreSubstituteAssignmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterAttendanceRequest;
use App\Http\Requests\RecordAttendanceRequest;
use App\Http\Requests\StoreSubstituteAssignmentRequest;
use App\Http\Requests\SwapAttendanceShiftRequest;
use App\Models\EmployeeAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AttendanceController extends Controller
{
    public function index(FilterAttendanceRequest $request, BuildAttendanceDashboardAction $action): View
    {
        return view('dashboard.attendance.index', $action->execute($request->user(), $request->validated()));
    }

    public function checkIn(RecordAttendanceRequest $request, EmployeeAssignment $assignment, RecordAttendanceAction $action): RedirectResponse
    {
        $action->checkIn($assignment);

        return back()->with('status', 'تم تسجيل الحضور بنجاح.');
    }

    public function checkOut(RecordAttendanceRequest $request, EmployeeAssignment $assignment, RecordAttendanceAction $action): RedirectResponse
    {
        $action->checkOut($assignment);

        return back()->with('status', 'تم تسجيل الانصراف بنجاح.');
    }

    public function markAbsent(RecordAttendanceRequest $request, EmployeeAssignment $assignment, RecordAttendanceAction $action): RedirectResponse
    {
        $action->markAbsent($assignment);

        return back()->with('status', 'تم تسجيل الموظف كغائب.');
    }

    public function swapShift(SwapAttendanceShiftRequest $request, EmployeeAssignment $assignment, RecordAttendanceAction $action): RedirectResponse
    {
        $action->swapShift($assignment, $request->integer('shift_id'));

        return back()->with('status', 'تم تبديل المناوبة بنجاح.');
    }

    public function substitute(StoreSubstituteAssignmentRequest $request, StoreSubstituteAssignmentAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return back()->with('status', 'تم تعيين الموظف البديل.');
    }
}
