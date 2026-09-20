<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'boat' => $this->whenLoaded('boat', fn () => ['id' => $this->boat->id, 'name' => $this->boat->name]),
            'type' => $this->whenLoaded('maintenanceType', fn () => $this->maintenanceType ? ['id' => $this->maintenanceType->id, 'name' => $this->maintenanceType->name] : null),
            'date' => $this->date?->toDateString(),
            'technician' => $this->technician,
            'estimated_cost' => $this->estimated_cost !== null ? (float) $this->estimated_cost : null,
            'description' => $this->description,
            'status' => $this->status,
        ];
    }
}
