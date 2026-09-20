<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consignment_number' => $this->consignment_number,
            'status' => $this->status,
            'dalal' => $this->whenLoaded('dalal', fn () => ['id' => $this->dalal->id, 'name' => $this->dalal->name, 'phone' => $this->dalal->phone]),
            'trip' => $this->whenLoaded('trip', fn () => $this->trip ? ['id' => $this->trip->id, 'trip_number' => $this->trip->trip_number] : null),
            'total_kg' => (float) $this->total_kg,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => ['id' => $item->id, 'species' => ['id' => $item->species->id, 'name' => $item->species->name_ar], 'weight_kg' => (float) $item->weight_kg])),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'notes' => $this->notes,
        ];
    }
}
