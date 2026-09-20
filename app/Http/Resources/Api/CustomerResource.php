<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'type' => $this->whenLoaded('customerType', fn () => $this->customerType ? ['id' => $this->customerType->id, 'name' => $this->customerType->name] : null),
            'region' => $this->whenLoaded('region', fn () => $this->region ? ['id' => $this->region->id, 'name' => $this->region->name] : null),
            'governorate' => $this->whenLoaded('governorate', fn () => $this->governorate ? ['id' => $this->governorate->id, 'name' => $this->governorate->name] : null),
            'notes' => $this->notes,
            'status' => $this->status,
            'sales_count' => $this->whenCounted('sales'),
            'sales_total' => $this->when(isset($this->sales_sum_total), fn () => (float) $this->sales_sum_total),
        ];
    }
}
