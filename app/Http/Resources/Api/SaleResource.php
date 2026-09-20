<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'status' => $this->status,
            'trip' => $this->whenLoaded('trip', fn () => $this->trip ? ['id' => $this->trip->id, 'trip_number' => $this->trip->trip_number] : null),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? ['id' => $this->customer->id, 'name' => $this->customer->name, 'phone' => $this->customer->phone] : null),
            'payment_method' => $this->whenLoaded('paymentMethod', fn () => $this->paymentMethod ? ['id' => $this->paymentMethod->id, 'name' => $this->paymentMethod->name] : null),
            'payment_status' => $this->whenLoaded('paymentStatus', fn () => $this->paymentStatus ? ['id' => $this->paymentStatus->id, 'name' => $this->paymentStatus->name] : null),
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'total' => (float) $this->total,
            'paid_amount' => (float) $this->paid_amount,
            'remaining' => $this->remaining,
            'owner_net' => (float) $this->owner_net,
            'items_count' => $this->whenCounted('items'),
            'total_kg' => $this->when(isset($this->items_sum_weight_kg), fn () => (float) $this->items_sum_weight_kg),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'sold_at' => $this->sold_at?->toIso8601String(),
            'notes' => $this->notes,
        ];
    }
}
