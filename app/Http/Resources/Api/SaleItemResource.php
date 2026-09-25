<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'species' => $this->whenLoaded('species', fn () => ['id' => $this->species->id, 'name' => $this->species->name_ar, 'name_sci' => $this->species->name_sci]),
            'trip' => $this->whenLoaded('trip', fn () => $this->trip ? ['id' => $this->trip->id, 'trip_number' => $this->trip->trip_number] : null),
            'owner' => $this->whenLoaded('owner', fn () => $this->owner ? ['id' => $this->owner->id, 'name' => $this->owner->name] : null),
            'weight_kg' => (float) $this->weight_kg,
            'price_per_kg' => (float) $this->price_per_kg,
            'total' => (float) $this->total,
            'commission_amount' => (float) $this->commission_amount,
            'wage_amount' => (float) $this->wage_amount,
            'owner_net' => (float) $this->owner_net,
        ];
    }
}
