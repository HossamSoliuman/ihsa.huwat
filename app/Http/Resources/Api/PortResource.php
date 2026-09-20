<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'governorate' => $this->whenLoaded('governorate', fn () => ['id' => $this->governorate->id, 'name' => $this->governorate->name, 'region' => $this->governorate->region?->name]),
        ];
    }
}
