<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\DocumentRequest;
use App\Models\Boat;
use App\Models\DocumentType;
use App\Models\Fisher;
use App\Models\FleetDocument;
use App\Models\User;
use App\Services\Owner\FleetService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DocumentController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly FleetService $fleet) {}

    public function index(Request $request): View
    {
        $owner = $request->user();
        $soon = now()->addDays(FleetDocument::EXPIRING_DAYS)->toDateString();
        $today = now()->toDateString();

        $rows = FleetDocument::forOwner($owner)
            ->with(['type', 'documentable'])
            ->when($request->query('holder') === 'boat', fn ($q) => $q->where('documentable_type', Boat::class))
            ->when($request->query('holder') === 'crew', fn ($q) => $q->where('documentable_type', Fisher::class))
            ->when($request->filled('type'), fn ($q) => $q->where('document_type_id', $request->query('type')))
            ->when($request->filled('search'), fn ($q) => $q->where('number', 'like', '%'.$request->query('search').'%'))
            ->when($request->query('status') === FleetDocument::EXPIRED, fn ($q) => $q->whereDate('expiry_date', '<', $today))
            ->when($request->query('status') === FleetDocument::EXPIRING, fn ($q) => $q->whereDate('expiry_date', '>=', $today)->whereDate('expiry_date', '<=', $soon))
            ->when($request->query('status') === FleetDocument::VALID, fn ($q) => $q->whereDate('expiry_date', '>', $soon))
            ->when($request->query('status') === FleetDocument::NO_EXPIRY, fn ($q) => $q->whereNull('expiry_date'))
            ->orderByRaw('expiry_date IS NULL, expiry_date')
            ->paginate(25)->withQueryString();

        $crew = Fisher::forOwner($owner)->with('documents.type')->orderBy('name')->get(['id', 'name', 'role', 'nationality']);
        $boats = Boat::forOwner($owner)->with('documents.type')->orderBy('name')->get(['id', 'name']);

        return view('panel.owner.documents.index', [
            'rows' => $rows,
            'counts' => $this->counts($owner),
            'crewCompliance' => $this->compliance($crew),
            'boatCompliance' => $this->compliance($boats),
            'boats' => $boats,
            'crew' => $crew,
            'types' => DocumentType::options(),
        ]);
    }

    public function store(DocumentRequest $request): RedirectResponse
    {
        $this->fleet->saveDocument($request->user(), $request->validated(), $request->file('attachment'));

        return back()->with('status', 'تمت إضافة الوثيقة.');
    }

    public function update(DocumentRequest $request, int $document): RedirectResponse
    {
        $row = $this->ownedDocument($request->user(), $document);
        $this->fleet->saveDocument($request->user(), $request->validated(), $request->file('attachment'), $request->boolean('remove_attachment'), $row);

        return back()->with('status', 'تم تحديث الوثيقة.');
    }

    public function destroy(Request $request, int $document): RedirectResponse
    {
        $this->fleet->deleteDocument($this->ownedDocument($request->user(), $document));

        return back()->with('status', 'تم حذف الوثيقة.');
    }

    private function counts(User $owner): array
    {
        $statuses = FleetDocument::forOwner($owner)->pluck('expiry_date')
            ->map(fn ($date) => FleetDocument::statusFor($date ? \Illuminate\Support\Carbon::parse($date) : null))
            ->countBy();

        return [
            'total' => $statuses->sum(),
            FleetDocument::EXPIRED => $statuses[FleetDocument::EXPIRED] ?? 0,
            FleetDocument::EXPIRING => $statuses[FleetDocument::EXPIRING] ?? 0,
            FleetDocument::VALID => ($statuses[FleetDocument::VALID] ?? 0) + ($statuses[FleetDocument::NO_EXPIRY] ?? 0),
        ];
    }

    /**
     * امتثال كل حائز (فحص الطاقم في hispa): بلا وثائق = ناقص، وثيقة منتهية =
     * غير ممتثل، تنتهي قريبًا = تنبيه، وإلا ممتثل؛ مع أقرب انتهاء.
     */
    private function compliance(Collection $holders): Collection
    {
        return $holders->map(function (Model $holder) {
            $docs = $holder->documents;
            $statuses = $docs->map->status;
            $nearest = $docs->whereNotNull('expiry_date')->sortBy('expiry_date')->first();

            return [
                'holder' => $holder,
                'count' => $docs->count(),
                'expired' => $statuses->filter(fn ($s) => $s === FleetDocument::EXPIRED)->count(),
                'expiring' => $statuses->filter(fn ($s) => $s === FleetDocument::EXPIRING)->count(),
                'nearest' => $nearest,
                'state' => match (true) {
                    $docs->isEmpty() => 'ناقص',
                    $statuses->contains(FleetDocument::EXPIRED) => 'غير ممتثل',
                    $statuses->contains(FleetDocument::EXPIRING) => 'تنبيه',
                    default => 'ممتثل',
                },
            ];
        });
    }
}
