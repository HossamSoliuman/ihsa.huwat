<?php

namespace App\Actions\Information;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Files a moderator account and the records it reaches in one go. The assignments are
 * written whole — the rows submitted replace whatever the account held — so removing a
 * port from the form removes the moderator's reach to it in the same save.
 */
final class SaveInformationModerator
{
    /**
     * @param  array{full_name: string, username: string, email?: string|null, phone: string, national_id: string, password?: string|null, is_active: bool, scope_type: string, scope_ids: list<int|string>}  $attributes
     */
    public function execute(array $attributes, ?User $moderator = null): User
    {
        return DB::transaction(function () use ($attributes, $moderator): User {
            $account = Arr::except($attributes, ['password', 'scope_type', 'scope_ids']);
            $account['role_id'] = $this->moderatorRoleId();

            /** An edit that left the password blank keeps the one already on the account. */
            if (($attributes['password'] ?? null) !== null && $attributes['password'] !== '') {
                $account['password_hash'] = Hash::make($attributes['password']);
            }

            $moderator = $moderator === null
                ? User::query()->create($account)
                : tap($moderator)->update($account);

            $moderator->assignedScopes()->delete();
            $moderator->assignedScopes()->createMany(array_map(
                static fn (int|string $scopeId): array => [
                    'scope_type' => $attributes['scope_type'],
                    'scope_id' => (int) $scopeId,
                ],
                array_values(array_unique($attributes['scope_ids'])),
            ));

            return $moderator;
        });
    }

    /** Seeded with the rest of the roles; missing, it is an environment fault worth raising. */
    private function moderatorRoleId(): int
    {
        return (int) Role::query()
            ->where('code', config('information.moderator_role'))
            ->firstOrFail()
            ->id;
    }
}
