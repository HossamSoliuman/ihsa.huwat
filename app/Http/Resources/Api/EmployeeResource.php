<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'nationality' => $this->nationality,
            'id_number' => $this->id_number,
            'job_title' => $this->whenLoaded('jobTitle', fn () => $this->jobTitle ? ['id' => $this->jobTitle->id, 'name' => $this->jobTitle->name] : null),
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
