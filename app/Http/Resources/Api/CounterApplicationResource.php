<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * طلب التوظيف كما يراه صاحبه. `token` مفتاح متابعته — يحفظه التطبيق بعد
 * التقديم ويستعمله في التوثيق والمتابعة والسحب.
 */
class CounterApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'token' => $this->token,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'phone_verified' => $this->isVerified(),
            'name' => $this->name,
            'phone' => $this->phone,
            'national_id' => $this->national_id,
            'email' => $this->email,
            'birth_date' => $this->birth_date?->toDateString(),
            'qualification' => $this->qualification,
            'experience_years' => $this->experience_years,
            'notes' => $this->notes,
            'rejection_reason' => $this->rejection_reason,
            'round' => ['id' => $this->round->id, 'title' => $this->round->title],
            'company' => ['id' => $this->company->id, 'name' => $this->company->name],
            'port' => ['id' => $this->port->id, 'name' => $this->port->name],
            'created_at' => $this->created_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
        ];
    }
}
