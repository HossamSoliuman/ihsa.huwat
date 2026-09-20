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
            'species' => $this->whenLoaded('species', fn () => ['id' => $this->species->id, 'name' => $this->species->name_ar]),
            'weight_kg' => (float) $this->weight_kg,
            'price_per_kg' => (float) $this->price_per_kg,
            'total' => (float) $this->total,
        ];
    }
}
