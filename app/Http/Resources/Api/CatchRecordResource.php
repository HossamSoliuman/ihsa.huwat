<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CatchRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'species' => $this->whenLoaded('species', fn () => ['id' => $this->species->id, 'name' => $this->species->name_ar, 'name_sci' => $this->species->name_sci]),
            'captain_kg' => $this->captain_kg !== null ? (float) $this->captain_kg : null,
            'counted_kg' => $this->counted_kg !== null ? (float) $this->counted_kg : null,
            'quantity_kg' => (float) $this->quantity_kg,
            'captain_notes' => $this->captain_notes,
            'counter_notes' => $this->counter_notes,
            'verified' => (bool) $this->verified,
            'added_by' => $this->whenLoaded('addedBy', fn () => $this->addedBy?->name),
            'recorded_at' => $this->recorded_at?->toDateString(),
        ];
    }
}
