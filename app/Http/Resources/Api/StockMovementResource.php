<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * سطر من دفتر المخزون — تقرير "حركات الصنف".
 */
class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'species' => $this->species?->name_ar,
            'trip_number' => $this->trip?->trip_number,
            'type' => $this->type?->name,
            'weight_kg' => (float) $this->weight_kg,
            'balance_after' => (float) $this->balance_after,
            'user' => $this->user?->name,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
