<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureInformationIdentity;
use App\Http\Requests\VerifyInformationIdentityRequest;
use App\Models\InformationSubmission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InformationIdentityController extends Controller
{
    /**
     * Landing page of the portal. Every visitor starts by confirming the national id
     * and phone their submissions are filed under.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $identity = EnsureInformationIdentity::verified($request);

        if ($identity !== null) {
            return to_route($this->destinationFor($identity));
        }

        return view('information.identity');
    }

    public function store(VerifyInformationIdentityRequest $request): RedirectResponse
    {
        /** @var array{national_id: string, phone: string} $identity */
        $identity = $request->validated();

        /** Rotate the session id so a pre-existing one cannot be replayed against the new identity. */
        $request->session()->regenerate();
        $request->session()->put(EnsureInformationIdentity::SESSION_KEY, $identity);

        return to_route($this->destinationFor($identity));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(EnsureInformationIdentity::SESSION_KEY);

        return to_route('information.identity.create');
    }

    /**
     * Returning applicants land on their tracker; anyone without a record yet goes
     * straight to the data entry form.
     *
     * @param  array{national_id: string, phone: string}  $identity
     */
    private function destinationFor(array $identity): string
    {
        return InformationSubmission::query()->forIdentity($identity)->exists()
            ? 'information.status.index'
            : 'information.create';
    }
}
