<?php

namespace App\Http\Resources\Api;

use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * إشعار كما يظهر في شاشة الإشعارات: النوع، العنوان، النص، الرحلة، ومقروء أم لا.
 *
 * @mixin AppNotification
 */
class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->whenLoaded('type', fn () => ['id' => $this->type->id, 'name' => $this->type->name, 'name_en' => $this->type->name_en, 'icon' => $this->type->icon]),
            'title' => $this->title,
            'body' => $this->body,
            'trip' => $this->whenLoaded('trip', fn () => $this->trip ? ['id' => $this->trip->id, 'trip_number' => $this->trip->trip_number, 'status' => $this->trip->status, 'app_status' => $this->trip->app_status] : null),
            // الاسم payload لا data: مفتاح data داخل المورد يمنع Laravel من تغليف الردّ بـ data.
            'payload' => $this->data ?? (object) [],
            'read' => $this->isRead(),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
