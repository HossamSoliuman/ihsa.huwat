<?php

namespace App\Http\Controllers\Concerns;

use App\Models\AppNotification;
use App\Models\Trip;
use App\Models\User;

/**
 * سجلات الكابتن تُقرأ مقيّدةً به: رحلة مسندة لغيره = 404، في الويب والـAPI.
 */
trait ResolvesCaptainRecords
{
    protected function captainTrip(User $captain, int|string $id): Trip
    {
        return Trip::forCaptain($captain)->findOrFail($id);
    }

    protected function ownNotification(User $user, int|string $id): AppNotification
    {
        return AppNotification::forUser($user)->findOrFail($id);
    }
}
