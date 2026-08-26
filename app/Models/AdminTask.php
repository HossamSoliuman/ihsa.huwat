<?php

namespace App\Models;

use Illuminate\Support\Carbon;

class AdminTask extends BaseModel
{
    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    /** الحالات النهائية — مهمة فيها لا تتأخر ولا تُنجَز مرة أخرى. */
    public const CLOSED = ['مكتملة', 'ملغاة'];

    public function position()
    {
        return $this->belongsTo(OrgPosition::class, 'org_position_id');
    }

    public function assignee()
    {
        return $this->belongsTo(OrgStaff::class, 'assigned_staff_id');
    }

    /**
     * متأخرة: إمّا سُجّلت كذلك، أو مرّ استحقاقها ولم تُغلق بعد.
     */
    public function isOverdue(): bool
    {
        if (in_array($this->status, self::CLOSED, true)) {
            return false;
        }

        return $this->status === 'متأخرة'
            || ($this->due_date !== null && $this->due_date->lt(Carbon::today()));
    }
}
