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
            'actual_cost' => $this->actual_cost !== null ? (float) $this->actual_cost : null,
            'description' => $this->description,
            'status' => $this->status,
            // المصروف المرحَّل حين تكتمل الصيانة بتكلفة.
            'expense' => $this->when($this->relationLoaded('expense'), fn () => $this->expense ? [
                'id' => $this->expense->id,
                'expense_number' => $this->expense->expense_number,
                'total' => (float) $this->expense->total,
                'paid_amount' => (float) $this->expense->paid_amount,
            ] : null),
        ];
    }
}
