<?php

namespace App\Models;

use Database\Factories\CounterTransferFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نقل عدّاد من ميناء إلى آخر — سجلّ تاريخي؛ الميناء الحالي في
 * statistics_officers.port_id.
 */
class CounterTransfer extends BaseModel
{
    /** @use HasFactory<CounterTransferFactory> */
    use HasFactory;

    public function officer(): BelongsTo
    {
        return $this->belongsTo(StatisticsOfficer::class, 'statistics_officer_id');
    }

    public function fromPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'from_port_id');
    }

    public function toPort(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'to_port_id');
    }

    public function mover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by');
    }
}
