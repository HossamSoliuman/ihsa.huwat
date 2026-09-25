<?php

namespace App\Http\Controllers\Panel\Dalal;

use App\Http\Controllers\Concerns\ResolvesDalalRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dalal\LogoRequest;
use App\Http\Requests\Dalal\SettingsRequest;
use App\Http\Requests\Dalal\WorkerRequest;
use App\Models\DalalProfile;
use App\Models\DalalWorker;
use App\Models\DalalWorkerType;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use App\Services\Dalal\DalalProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * إعدادات الدلال: الملف التجاري والدكة، والشركة وشعارها، وعمالة الدكة.
 * كلمة المرور والاسم والصورة في الملف الشخصي المشترك (panel.profile).
 */
class SettingsController extends Controller
{
    use ResolvesDalalRecords;

    public function __construct(private readonly DalalProfileService $profiles) {}

    public function index(Request $request): View
    {
        $dalal = $request->user();

        return view('panel.dalal.settings.index', [
            'user' => $dalal,
            'profile' => DalalProfile::forUser($dalal),
            'workers' => DalalWorker::forDalal($dalal)->with('type')->orderBy('id')->get(),
            'workerTypes' => DalalWorkerType::options(),
            'regions' => Region::orderBy('name')->get(['id', 'name']),
            'governorates' => Governorate::orderBy('name')->get(['id', 'name', 'region_id']),
            'ports' => Port::orderBy('name')->get(['id', 'name', 'governorate_id']),
            'tab' => $request->query('tab', 'profile'),
        ]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        $this->profiles->update($request->user(), $request->validated());

        return back()->with('status', 'حُفظت الإعدادات.');
    }

    public function logo(LogoRequest $request): RedirectResponse
    {
        $this->profiles->storeLogo($request->user(), $request->file('logo'));

        return redirect()->route('panel.dalal.settings', ['tab' => 'company'])->with('status', 'حُفظ الشعار.');
    }

    public function removeLogo(Request $request): RedirectResponse
    {
        $this->profiles->removeLogo($request->user());

        return redirect()->route('panel.dalal.settings', ['tab' => 'company'])->with('status', 'حُذف الشعار.');
    }

    public function storeWorker(WorkerRequest $request): RedirectResponse
    {
        $this->profiles->addWorker($request->user(), $request->validated());

        return redirect()->route('panel.dalal.settings', ['tab' => 'workers'])->with('status', 'أُضيف سطر العمالة.');
    }

    public function destroyWorker(Request $request, int $worker): RedirectResponse
    {
        $this->dalalWorker($request->user(), $worker)->delete();

        return redirect()->route('panel.dalal.settings', ['tab' => 'workers'])->with('status', 'حُذف سطر العمالة.');
    }
}
