<?php

namespace App\Http\Controllers;

use App\Actions\StoreInformationSubmissionAction;
use App\Http\Requests\StoreInformationDraftRequest;
use App\Http\Requests\StoreInformationSubmissionRequest;
use App\Models\InformationDraft;
use App\Models\Port;
use App\Models\Region;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class InformationPortalController extends Controller
{
    public function create(Request $request): View
    {
        $draft = InformationDraft::query()->where('user_id', $request->user()->getKey())->first();

        return view('information.create', [
            'ports' => Port::query()->with('governorate')->where('is_active', true)->orderBy('name')->get(),
            'regions' => Region::query()
                ->with(['governorates' => fn (HasMany $query): HasMany => $query->orderBy('name')])
                ->orderBy('name')
                ->get(),
            'draft' => $draft,
        ]);
    }

    public function storeDraft(StoreInformationDraftRequest $request): JsonResponse
    {
        $draft = InformationDraft::query()->updateOrCreate(
            ['user_id' => $request->user()->getKey()],
            $request->safe()->only(['payload', 'current_step']),
        );

        return response()->json([
            'message' => 'تم حفظ المسودة.',
            'saved_at' => $draft->updated_at->toISOString(),
        ]);
    }

    public function discardDraft(Request $request): Response
    {
        InformationDraft::query()->where('user_id', $request->user()->getKey())->delete();

        return response()->noContent();
    }

    public function store(
        StoreInformationSubmissionRequest $request,
        StoreInformationSubmissionAction $storeInformationSubmission,
    ): RedirectResponse {
        $documents = $request->file('documents', []);
        $captainPhoto = $request->file('captain_photo');

        $submission = $storeInformationSubmission->handle(
            $request->user(),
            Arr::except($request->validated(), ['website', 'documents', 'captain_photo', 'consent']),
            is_array($documents) ? $documents : [],
            $captainPhoto instanceof UploadedFile ? $captainPhoto : null,
        );

        InformationDraft::query()->where('user_id', $request->user()->getKey())->delete();

        $request->session()->put('information_receipts.'.$submission->reference_no, [
            'reference' => $submission->reference_no,
            'boat_name' => $submission->boat->name,
            'port_name' => $submission->port->name,
            'crew_count' => $submission->crew_count,
            'tool_count' => count($submission->fishing_tools ?? []),
            'document_count' => count($submission->document_paths ?? []),
            'submitted_at' => $submission->submitted_at->toISOString(),
        ]);

        return redirect()->route('information.submitted', $submission->reference_no);
    }

    public function submitted(Request $request, string $reference): View
    {
        abort_unless(preg_match('/^INFO-[0-9]{8}-[A-Z0-9]{6}$/', $reference), 404);

        $receipt = $request->session()->get('information_receipts.'.$reference);
        abort_unless($receipt, 404);

        return view('information.submitted', ['receipt' => $receipt]);
    }
}
