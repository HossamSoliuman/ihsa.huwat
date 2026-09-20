<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * الكابتن = حساب دخول + سجلّ صياد.
 */
class CaptainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'active' => (bool) $this->active,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'fisher' => $this->whenLoaded('fisher', fn () => $this->fisher ? new FisherResource($this->fisher) : null),
        ];
    }
}
