<?php

namespace App\Http\Controllers;

use App\Actions\Information\SaveInformationModerator;
use App\Http\Requests\FilterInformationModeratorsRequest;
use App\Http\Requests\ManageInformationModeratorRequest;
use App\Http\Requests\StoreInformationModeratorRequest;
use App\Http\Requests\UpdateInformationModeratorRequest;
use App\Models\FishMarket;
use App\Models\Governorate;
use App\Models\Port;
use App\Models\Region;
use App\Models\User;
use App\Models\UserScope;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

/**
 * المشرفون. An account the desk opens for someone who answers for a handful of ports,
 * markets, governorates or regions — and for nothing else. The records assigned here are
 * the whole of what that account sees once it signs in, on every screen of the centre.
 */
class InformationModeratorController extends Controller
{
    public function index(FilterInformationModeratorsRequest $request): View
    {
        $filters = $request->validated();

        $moderators = $this->moderators()
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query
                ->where('is_active', $status === 'active'))
            ->when($filters['scope_type'] ?? null, fn (Builder $query, string $scopeType): Builder => $query
                ->whereHas('assignedScopes', fn (Builder $scopes): Builder => $scopes->where('scope_type', $scopeType)))
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        return view('information.admin.moderators.index', [
            'moderators' => $moderators,
            'filters' => $filters,
            'scopeLabels' => $this->scopeLabels(),
        ]);
    }

    public function create(ManageInformationModeratorRequest $request): View
    {
        return view('information.admin.moderators.create', $this->assignableRecords());
    }

    public function store(StoreInformationModeratorRequest $request, SaveInformationModerator $saveModerator): RedirectResponse
    {
        $moderator = $saveModerator->execute($request->validated());

        return to_route('information.admin.moderators.show', $moderator)->with('status', 'تم إنشاء حساب المشرف.');
    }

    public function show(ManageInformationModeratorRequest $request, User $moderator): View
    {
        $this->abortUnlessModerator($moderator);
        $moderator->load('assignedScopes');

        return view('information.admin.moderators.show', [
            'moderator' => $moderator,
            ...$this->assignableRecords(),
        ]);
    }

    public function update(
        UpdateInformationModeratorRequest $request,
        User $moderator,
        SaveInformationModerator $saveModerator,
    ): RedirectResponse {
        $this->abortUnlessModerator($moderator);
        $saveModerator->execute($request->validated(), $moderator);

        return to_route('information.admin.moderators.show', $moderator)->with('status', 'تم تحديث حساب المشرف.');
    }

    public function destroy(ManageInformationModeratorRequest $request, User $moderator): RedirectResponse
    {
        $this->abortUnlessModerator($moderator);
        $moderator->delete();

        return to_route('information.admin.moderators.index')->with('status', 'تم حذف حساب المشرف.');
    }

    /** @return Builder<User> */
    private function moderators(): Builder
    {
        return User::query()
            ->with('assignedScopes')
            ->whereHas('role', fn (Builder $role): Builder => $role->where('code', config('information.moderator_role')));
    }

    /**
     * Only a moderator account is edited here. Every other account — the desk's own included
     * — is out of this screen's reach, so a hand-typed id cannot rewrite an administrator.
     */
    private function abortUnlessModerator(User $moderator): void
    {
        abort_unless(
            $moderator->role()->value('code') === config('information.moderator_role'),
            404,
        );
    }

    /**
     * Every record a moderator may be assigned, grouped by level. The whole set is sent at
     * once and the page shows the list belonging to the level in play, so switching level
     * costs no round trip.
     *
     * @return array<string, Collection<int, mixed>>
     */
    private function assignableRecords(): array
    {
        return [
            'regions' => Region::query()->orderBy('name')->get(['id', 'name']),
            'governorates' => Governorate::query()->with('region:id,name')->orderBy('name')->get(['id', 'region_id', 'name']),
            'ports' => Port::query()->with('governorate:id,name')->orderBy('name')->get(['id', 'governorate_id', 'name']),
            'markets' => FishMarket::query()->with('governorate:id,name')->ordered()->get(['id', 'governorate_id', 'name']),
        ];
    }

    /**
     * Names for the records already assigned, so the list reads as ميناء القنفذة rather than
     * as a number. Read in four queries whatever the page holds.
     *
     * @return array<string, array<int, string>>
     */
    private function scopeLabels(): array
    {
        return [
            UserScope::TYPE_REGION => Region::query()->pluck('name', 'id')->all(),
            UserScope::TYPE_GOVERNORATE => Governorate::query()->pluck('name', 'id')->all(),
            UserScope::TYPE_PORT => Port::query()->pluck('name', 'id')->all(),
            UserScope::TYPE_MARKET => FishMarket::query()->pluck('name', 'id')->all(),
        ];
    }
}
