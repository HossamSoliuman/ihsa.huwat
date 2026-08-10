<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManageHumanResourcesSettingsRequest;
use App\Http\Requests\StoreEmploymentLookupOptionRequest;
use App\Http\Requests\UpdateEmploymentLookupOptionRequest;
use App\Http\Requests\UpdateLeaveTypeRequest;
use App\Http\Requests\UpdateSalaryComponentRequest;
use App\Http\Requests\UpdateShiftSettingsRequest;
use App\Models\Bank;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\LeaveType;
use App\Models\LookupList;
use App\Models\SalaryComponent;
use App\Models\Shift;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class HumanResourcesSettingsController extends Controller
{
    /** @var array<string, string> */
    public const SECTIONS = [
        'departments' => 'الأقسام',
        'job_titles' => 'المسميات الوظيفية',
        'banks' => 'البنوك',
        'leave_types' => 'أنواع الإجازات',
        'salary_components' => 'مكوّنات الراتب',
        'shifts' => 'المناوبات',
    ];

    /** @var array<string, class-string<LookupList>> */
    private const OPTION_LISTS = [
        'departments' => Department::class,
        'job_titles' => JobTitle::class,
        'banks' => Bank::class,
    ];

    public function index(ManageHumanResourcesSettingsRequest $request): View
    {
        $section = $request->validated('section', 'departments');

        return view('dashboard.hr.settings.index', [
            'section' => $section,
            'sections' => self::SECTIONS,
            ...$this->sectionData($section),
        ]);
    }

    public function storeOption(StoreEmploymentLookupOptionRequest $request, string $list): RedirectResponse
    {
        $model = self::optionModel($list);

        $model::query()->create([
            ...$request->validated(),
            'sort_order' => (int) $model::query()->max('sort_order') + 10,
        ]);

        return $this->back($list, 'تمت إضافة الخيار.');
    }

    public function updateOption(
        UpdateEmploymentLookupOptionRequest $request,
        string $list,
        int $option,
    ): RedirectResponse {
        $this->option($list, $option)->update($request->validated());

        return $this->back($list, 'تم تحديث الخيار.');
    }

    public function toggleOption(
        ManageHumanResourcesSettingsRequest $request,
        string $list,
        int $option,
    ): RedirectResponse {
        $record = $this->option($list, $option);
        $record->update(['is_active' => ! $record->is_active]);

        return $this->back($list, $record->is_active ? 'تم تفعيل الخيار.' : 'تم إيقاف الخيار.');
    }

    public function destroyOption(
        ManageHumanResourcesSettingsRequest $request,
        string $list,
        int $option,
    ): RedirectResponse {
        $record = $this->option($list, $option);
        abort_if($record->is_active, 403);
        $record->delete();

        return $this->back($list, 'تم حذف الخيار نهائياً.');
    }

    public function updateLeaveType(
        UpdateLeaveTypeRequest $request,
        LeaveType $leaveType,
    ): RedirectResponse {
        $leaveType->update($request->validated());

        return $this->back('leave_types', 'تم تحديث نوع الإجازة.');
    }

    public function toggleLeaveType(
        ManageHumanResourcesSettingsRequest $request,
        LeaveType $leaveType,
    ): RedirectResponse {
        $leaveType->update(['is_active' => ! $leaveType->is_active]);

        return $this->back('leave_types', $leaveType->is_active ? 'تم تفعيل نوع الإجازة.' : 'تم إيقاف نوع الإجازة.');
    }

    public function updateSalaryComponent(
        UpdateSalaryComponentRequest $request,
        SalaryComponent $salaryComponent,
    ): RedirectResponse {
        $salaryComponent->update($request->validated());

        return $this->back('salary_components', 'تم تحديث مكوّن الراتب.');
    }

    public function toggleSalaryComponent(
        ManageHumanResourcesSettingsRequest $request,
        SalaryComponent $salaryComponent,
    ): RedirectResponse {
        abort_if($salaryComponent->is_basic, 403);
        $salaryComponent->update(['is_active' => ! $salaryComponent->is_active]);

        return $this->back('salary_components', $salaryComponent->is_active ? 'تم تفعيل مكوّن الراتب.' : 'تم إيقاف مكوّن الراتب.');
    }

    public function updateShift(UpdateShiftSettingsRequest $request, Shift $shift): RedirectResponse
    {
        $data = $request->validated();
        $data['crosses_midnight'] = $data['end_time'] <= $data['start_time'];
        $shift->update($data);

        return $this->back('shifts', 'تم تحديث المناوبة.');
    }

    public function toggleShift(
        ManageHumanResourcesSettingsRequest $request,
        Shift $shift,
    ): RedirectResponse {
        $shift->update(['is_active' => ! $shift->is_active]);

        return $this->back('shifts', $shift->is_active ? 'تم تفعيل المناوبة.' : 'تم إيقاف المناوبة.');
    }

    /** @return class-string<LookupList> */
    public static function optionModel(string $list): string
    {
        abort_unless(array_key_exists($list, self::OPTION_LISTS), 404);

        return self::OPTION_LISTS[$list];
    }

    /** @return array<string, mixed> */
    private function sectionData(string $section): array
    {
        if (array_key_exists($section, self::OPTION_LISTS)) {
            $model = self::optionModel($section);

            return ['options' => $model::query()->ordered()->get()];
        }

        return match ($section) {
            'leave_types' => ['leaveTypes' => LeaveType::query()->ordered()->get()],
            'salary_components' => ['salaryComponents' => SalaryComponent::query()->ordered()->get()],
            'shifts' => ['shifts' => Shift::query()->orderBy('start_time')->orderBy('id')->get()],
            default => [],
        };
    }

    private function option(string $list, int $option): Model
    {
        return self::optionModel($list)::query()->findOrFail($option);
    }

    private function back(string $section, string $status): RedirectResponse
    {
        return to_route('dashboard.hr.settings.index', ['section' => $section])->with('status', $status);
    }
}
