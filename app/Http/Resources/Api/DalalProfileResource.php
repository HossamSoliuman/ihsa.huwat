<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * إعدادات الدلال: ملفه التجاري والدكة، والشركة وشعارها، وعمالة الدكة.
 */
class DalalProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ref = fn ($model) => $model ? ['id' => $model->id, 'name' => $model->name] : null;

        return [
            'id_number' => $this->id_number,
            'region' => $ref($this->region),
            'governorate' => $ref($this->governorate),
            'port' => $ref($this->port),
            'dakka_name' => $this->dakka_name,
            'dakka_number' => $this->dakka_number,
            'company_name' => $this->company_name,
            'cr_number' => $this->cr_number,
            'vat_number' => $this->vat_number,
            'company_email' => $this->company_email,
            'company_phone' => $this->company_phone,
            'address' => $this->address,
            'website' => $this->website,
            'logo_url' => $this->logo_url,
            'workers' => $this->whenLoaded('user', fn () => $this->user->dalalWorkers->map(fn ($worker) => [
                'id' => $worker->id,
                'type' => $ref($worker->type),
                'nationality' => $worker->nationality,
                'count' => (int) $worker->count,
                'notes' => $worker->notes,
            ])->values()),
        ];
    }
}
