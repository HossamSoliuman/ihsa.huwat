<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * جولة توظيف مفتوحة كما يعرضها التطبيق في شاشة التقديم: المنطقة ←
 * المحافظة ← الميناء، والشركة، والمقاعد الباقية.
 */
class HiringRoundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $governorate = $this->port?->governorate;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'seats' => $this->seats,
            'seats_left' => $this->seatsLeft(),
            'opens_at' => $this->opens_at->toDateString(),
            'closes_at' => $this->closes_at->toDateString(),
            'notes' => $this->notes,
            'company' => ['id' => $this->company->id, 'name' => $this->company->name],
            'port' => ['id' => $this->port->id, 'name' => $this->port->name],
            'governorate' => $governorate ? ['id' => $governorate->id, 'name' => $governorate->name] : null,
            'region' => $governorate?->region ? ['id' => $governorate->region->id, 'name' => $governorate->region->name] : null,
        ];
    }
}
