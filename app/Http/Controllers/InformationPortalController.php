<?php

namespace App\Http\Controllers;

use App\Actions\StoreInformationSubmissionAction;
use App\Http\Middleware\EnsureInformationIdentity;
use App\Http\Requests\StoreInformationSubmissionRequest;
use App\Models\Port;
use App\Models\Region;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class InformationPortalController extends Controller
{
    public function create(Request $request): View
    {
        return view('information.create', [
            'identity' => EnsureInformationIdentity::verified($request),
            /** Retired geography stays out of the pickers, so nobody files under a region the desk switched off. */
            'ports' => Port::query()->selectable()->with('governorate')->orderBy('name')->get(),
            'regions' => Region::query()
                ->where('is_active', true)
                ->with(['governorates' => fn (HasMany $query): HasMany => $query->where('is_active', true)->orderBy('name')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(
        StoreInformationSubmissionRequest $request,
        StoreInformationSubmissionAction $storeInformationSubmission,
    ): RedirectResponse {
        $documents = $request->file('documents', []);
        $captainPhoto = $request->file('captain_photo');
        $crewPhotos = $request->file('crew_photos', []);

        $submission = $storeInformationSubmission->handle(
            $request->user(),
            Arr::except($request->validated(), ['website', 'documents', 'captain_photo', 'crew_photos', 'consent']),
            is_array($documents) ? $documents : [],
            $captainPhoto instanceof UploadedFile ? $captainPhoto : null,
            is_array($crewPhotos) ? $crewPhotos : [],
        );

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
