<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsignmentItem extends BaseModel
{
    use HasFactory;

    public function consignment(): BelongsTo
    {
        return $this->belongsTo(Consignment::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(Species::class);
    }
}
