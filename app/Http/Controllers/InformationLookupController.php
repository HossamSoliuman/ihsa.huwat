<?php

namespace App\Http\Controllers;

use App\Actions\DeleteMasterDataRecordAction;
use App\Http\Requests\ManageInformationLookupRequest;
use App\Http\Requests\StoreInformationLookupOptionRequest;
use App\Http\Requests\StoreInformationReferenceRequest;
use App\Http\Requests\UpdateInformationLookupOptionRequest;
use App\Http\Requests\ViewInformationLookupsRequest;
use App\Models\BoatClassification;
use App\Models\BoatType;
use App\Models\City;
use App\Models\CrewRole;
use App\Models\FishingMethod;
use App\Models\FishingToolCondition;
use App\Models\FishingToolMaterial;
use App\Models\FishingToolType;
use App\Models\FishSpecies;
use App\Models\Governorate;
use App\Models\HullMaterial;
use App\Models\LookupList;
use App\Models\Nationality;
use App\Models\Port;
use App\Models\Region;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * Maintenance desk for every list an applicant picks from on the portal form —
 * the geography records behind المنطقة/المحافظة/المدينة/الميناء, the option lists
 * seeded from `config/information.php`, and the fish species catalogue.
 */
class InformationLookupController extends Controller
{
    /** @var array<string, string> */
    public const TABS = [
        'regions' => 'المناطق',
        'governorates' => 'المحافظات',
        'cities' => 'المدن',
        'ports' => 'الموانئ',
        'boats' => 'بيانات القوارب',
        'crew' => 'المالك والطاقم',
        'fishing' => 'الصيد والأدوات',
        'species' => 'أنواع الأسماك',
    ];

    /**
     * Option lists grouped into the tab that owns them.
     *
     * @var array<string, list<class-string<LookupList>>>
     */
    private const OPTION_TABS = [
        'boats' => [BoatType::class, BoatClassification::class, HullMaterial::class],
        'crew' => [Nationality::class, CrewRole::class],
        'fishing' => [FishingMethod::class, FishingToolType::class, FishingToolMaterial::class, FishingToolCondition::class],
    ];

    /**
     * Reference records addressed by their tab key: the model behind them, the Arabic
     * noun used in messages, and everything that must be clear before one can be deleted.
     *
     * @var array<string, array{model: class-string<Model>, label: string, dependencies: array<string, string>, values: list<array{0: string, 1: string, 2: string}>}>
     */
    public const REFERENCES = [
        'regions' => [
            'model' => Region::class,
            'label' => 'المنطقة',
            'dependencies' => ['governorates' => 'region_id', 'users' => 'region_id', 'seasons' => 'region_id'],
            'values' => [['information_submissions', 'owner_region', 'name']],
        ],
        'governorates' => [
            'model' => Governorate::class,
            'label' => 'المحافظة',
            'dependencies' => ['cities' => 'governorate_id', 'ports' => 'governorate_id', 'users' => 'governorate_id'],
            'values' => [['information_submissions', 'owner_governorate', 'name']],
        ],
        'cities' => [
            'model' => City::class,
            'label' => 'المدينة',
            'dependencies' => [],
            'values' => [['information_submissions', 'owner_city', 'name']],
        ],
        'ports' => [
            'model' => Port::class,
            'label' => 'الميناء',
            'dependencies' => [
                'information_submissions' => 'port_id', 'users' => 'port_id', 'employee_assignments' => 'port_id',
                'boats' => 'home_port_id', 'trips' => 'port_id', 'harbor_boat_capacities' => 'port_id',
                'harbor_workers' => 'port_id', 'harbor_licenses' => 'port_id', 'harbor_violations' => 'port_id',
                'employment_jobs' => 'port_id', 'employment_applications' => 'preferred_port_id',
            ],
            'values' => [],
        ],
        'species' => [
            'model' => FishSpecies::class,
            'label' => 'نوع السمك',
            'dependencies' => ['catch_details' => 'species_id'],
            'values' => [],
        ],
    ];

    public function index(ViewInformationLookupsRequest $request): View
    {
        $tab = $request->validated('tab', 'regions');

        return view('information.admin.lookups', [
            'tab' => $tab,
            'tabs' => self::TABS,
            'optionLists' => $this->optionLists($tab),
            ...$this->references($tab),
        ]);
    }

    public function storeOption(StoreInformationLookupOptionRequest $request, string $list): RedirectResponse
    {
        $model = LookupList::resolve($list);

        $model::query()->create([
            ...$request->validated(),
            'sort_order' => (int) $model::query()->max('sort_order') + 10,
        ]);

        return $this->back($this->tabForList($list), 'تمت إضافة الخيار إلى القائمة.');
    }

    public function updateOption(UpdateInformationLookupOptionRequest $request, string $list, int $option): RedirectResponse
    {
        $this->option($list, $option)->update($request->validated());

        return $this->back($this->tabForList($list), 'تم تحديث الخيار.');
    }

    public function toggleOption(ManageInformationLookupRequest $request, string $list, int $option): RedirectResponse
    {
        $record = $this->option($list, $option);
        $record->update(['is_active' => ! $record->is_active]);

        return $this->back(
            $this->tabForList($list),
            $record->is_active ? 'تم تفعيل الخيار في النموذج.' : 'تم إيقاف الخيار، ولن يظهر للمتقدمين.',
        );
    }

    public function destroyOption(ManageInformationLookupRequest $request, string $list, int $option): RedirectResponse
    {
        $record = $this->option($list, $option);

        /** Retiring an option first is what keeps a live list from losing a value by accident. */
        abort_if($record->is_active, 403);

        $record->delete();

        return $this->back($this->tabForList($list), 'تم حذف الخيار نهائياً.');
    }

    public function storeReference(StoreInformationReferenceRequest $request, string $type): RedirectResponse
    {
        $reference = self::REFERENCES[$type];
        $reference['model']::query()->create($request->validated());

        return $this->back($type, 'تمت إضافة '.$reference['label'].'.');
    }

    public function toggleReference(ManageInformationLookupRequest $request, string $type, int $record): RedirectResponse
    {
        abort_unless($type === 'ports', 404);

        $port = Port::query()->findOrFail($record);
        $port->update(['is_active' => ! $port->is_active]);

        return $this->back($type, $port->is_active ? 'تم تفعيل الميناء.' : 'تم تعطيل الميناء.');
    }

    public function destroyReference(
        ManageInformationLookupRequest $request,
        string $type,
        int $record,
        DeleteMasterDataRecordAction $deleteMasterDataRecord,
    ): RedirectResponse {
        $reference = self::REFERENCES[$type];

        $deleteMasterDataRecord->execute(
            $reference['model']::query()->findOrFail($record),
            $reference['dependencies'],
            $reference['label'],
            $reference['values'],
        );

        return $this->back($type, 'تم حذف '.$reference['label'].'.');
    }

    /**
     * Option lists shown on the given tab, retired values included so they stay editable.
     *
     * @return list<array{key: string, title: string, options: Collection<int, LookupList>}>
     */
    private function optionLists(string $tab): array
    {
        return array_map(fn (string $list): array => [
            'key' => $list::key(),
            'title' => $list::title(),
            'options' => $list::query()->ordered()->get(),
        ], self::OPTION_TABS[$tab] ?? []);
    }

    /** @return LookupList */
    private function option(string $list, int $option): Model
    {
        return LookupList::resolve($list)::query()->findOrFail($option);
    }

    /** @return array<string, mixed> */
    private function references(string $tab): array
    {
        return match ($tab) {
            'governorates' => [
                'records' => Governorate::query()->with('region')->withCount(['cities', 'ports'])->orderBy('name')->get(),
                'regions' => Region::query()->orderBy('name')->get(),
            ],
            'cities' => [
                'records' => City::query()->with('governorate.region')->orderBy('name')->get(),
                'governorates' => Governorate::query()->orderBy('name')->get(),
                'regions' => Region::query()->orderBy('name')->get(),
            ],
            'ports' => [
                'records' => Port::query()->with('governorate.region')->orderBy('name')->get(),
                'governorates' => Governorate::query()->orderBy('name')->get(),
                'regions' => Region::query()->orderBy('name')->get(),
            ],
            'species' => ['records' => FishSpecies::query()->with('family')->withCount('catchDetails')->ordered()->get()],
            'regions' => ['records' => Region::query()->withCount(['governorates'])->orderBy('name')->get()],
            default => [],
        };
    }

    private function tabForList(string $list): string
    {
        foreach (self::OPTION_TABS as $tab => $lists) {
            foreach ($lists as $model) {
                if ($model::key() === $list) {
                    return $tab;
                }
            }
        }

        return 'regions';
    }

    private function back(string $tab, string $status): RedirectResponse
    {
        return to_route('information.admin.lookups.index', ['tab' => $tab])->with('status', $status);
    }
}
