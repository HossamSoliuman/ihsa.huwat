<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Trip;
use App\Models\User;

/**
 * رحلات العدّاد تُقرأ مقيّدةً بميناء عمله وبطور العد: رحلة ميناء آخر، أو
 * رحلة لم تعد بعد، = 404 في الويب والـAPI (انظر Trip::scopeForCounter).
 */
trait ResolvesCounterRecords
{
    protected function counterTrip(User $counter, int|string $id): Trip
    {
        return Trip::forCounter($counter)->findOrFail($id);
    }
}
