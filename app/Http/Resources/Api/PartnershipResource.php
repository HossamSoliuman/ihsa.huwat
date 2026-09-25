<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'owner' => $this->whenLoaded('owner', fn () => ['id' => $this->owner->id, 'name' => $this->owner->name, 'phone' => $this->owner->phone, 'email' => $this->owner->email]),
            'dalal' => $this->whenLoaded('dalal', fn () => ['id' => $this->dalal->id, 'name' => $this->dalal->name, 'phone' => $this->dalal->phone]),
            'commission_pct' => (float) $this->commission_pct,
            'wage_pct' => (float) $this->wage_pct,
            'message' => $this->message,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'response_note' => $this->response_note,
            'responded_at' => $this->responded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
