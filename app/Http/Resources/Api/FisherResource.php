<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * سجلّ صياد (كابتن أو طاقم) كما يراه المالك.
 */
class FisherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'national_id' => $this->national_id,
            'nationality' => $this->nationality,
            'id_type' => $this->whenLoaded('idType', fn () => $this->idType ? ['id' => $this->idType->id, 'name' => $this->idType->name] : null),
            'role' => $this->whenLoaded('fisherRole', fn () => $this->fisherRole ? ['id' => $this->fisherRole->id, 'name' => $this->fisherRole->name] : ['id' => null, 'name' => $this->role]),
            'boat' => $this->whenLoaded('boat', fn () => $this->boat ? ['id' => $this->boat->id, 'name' => $this->boat->name] : null),
            'port' => $this->whenLoaded('port', fn () => $this->port ? ['id' => $this->port->id, 'name' => $this->port->name] : null),
            'license_number' => $this->license_number,
            'license_status' => $this->license_status,
            'license_expiry' => $this->license_expiry?->toDateString(),
            'experience_years' => (int) $this->experience_years,
            'trips_count' => (int) $this->trips_count,
            'status' => $this->status,
        ];
    }
}
