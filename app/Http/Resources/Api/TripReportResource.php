<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * التقرير المفصّل للرحلة في شاشة العدّاد. الأقسام تصل كما بناها
 * App\Services\Counter\TripReport — عنوان القسم ثم سطوره (تسمية → قيمة) —
 * فيعرضها التطبيق كما هي دون أن يعرف أسماء الأعمدة.
 */
class TripReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'trip' => new TripResource($this->resource['trip']),
            'sections' => collect($this->resource['sections'])->map(fn (array $rows, string $title) => [
                'title' => $title,
                // السطر الفارغ لا يُرسل: التطبيق يعرض ما وصله لا شرطات.
                'rows' => collect($rows)->filter(fn ($value) => $value !== null && $value !== '')
                    ->map(fn ($value, $label) => ['label' => $label, 'value' => (string) $value])
                    ->values(),
            ])->values(),
            'catch' => $this->resource['catch'],
            'totals' => $this->resource['totals'],
        ];
    }
}
