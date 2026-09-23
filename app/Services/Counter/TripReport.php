<?php

namespace App\Services\Counter;

use App\Models\Trip;

/**
 * "تقرير مفصّل للرحلة" في شاشة العدّاد: الرحلة وطاقمها ومركبها ورخصتها
 * وتفاصيل مصيدها في بنية واحدة يقرؤها الويب والتطبيق — أقسام معنونة
 * بسطور (تسمية، قيمة) حتى تُعرض كما هي في الشاشتين دون تكرار الصياغة.
 */
class TripReport
{
    public function for(Trip $trip): array
    {
        $trip->loadMissing([
            'boat.port.governorate.region', 'owner', 'captain', 'counter',
            'departurePort', 'returnPort', 'tripType', 'catchRecords.species', 'catchRecords.addedBy',
        ]);

        return [
            'trip' => $trip,
            'sections' => [
                'الرحلة' => [
                    'رقم الرحلة' => $trip->trip_number,
                    'نوع التصريح' => $trip->tripType?->name,
                    'الحالة' => $trip->app_status,
                    'حالة الوزارة' => $trip->status,
                    'تاريخ ووقت البداية' => $this->at($trip->started_at ?? $trip->departure_time),
                    'تاريخ ووقت العودة' => $this->at($trip->return_time),
                    'المدة' => $trip->duration_hours !== null ? number_format((float) $trip->duration_hours, 1).' ساعة' : null,
                    'مدة الرحلة المخطّطة' => $trip->planned_days ? $trip->planned_days.' يوم' : null,
                    'عدد الطاقم' => (string) $trip->crew_count,
                    'أداة الصيد' => $trip->gear_type,
                ],
                'الموقع' => [
                    'المنطقة' => $trip->boat?->port?->governorate?->region?->name,
                    'المحافظة' => $trip->boat?->port?->governorate?->name,
                    'ميناء المغادرة' => $trip->departurePort?->name,
                    'ميناء العودة' => $trip->returnPort?->name ?? $trip->departurePort?->name,
                ],
                'الطاقم' => [
                    'الصياد (المالك)' => $trip->owner?->name,
                    'جوال المالك' => $trip->owner?->phone,
                    'القبطان' => $trip->captain?->name ?? $trip->captain_name,
                    'جوال القبطان' => $trip->captain?->phone,
                    'العدّاد' => $trip->counter?->name ?? $trip->statistics_officer,
                ],
                'المركب' => [
                    'الاسم' => $trip->boat?->name,
                    'الرقم' => $trip->boat?->boat_number,
                    'اللون' => $trip->boat?->color,
                    'الطول' => $trip->boat?->length_m ? number_format((float) $trip->boat->length_m, 2).' م' : null,
                    'العرض' => $trip->boat?->width_m ? number_format((float) $trip->boat->width_m, 2).' م' : null,
                    'رقم الهيكل' => $trip->boat?->hull_number,
                    'سعة الطاقم' => $trip->boat?->crew_capacity ? (string) $trip->boat->crew_capacity : null,
                ],
                'الرخصة' => [
                    'رقم رخصة الرحلة' => $trip->license_number,
                    'رقم رخصة المركب' => $trip->boat?->license_number,
                    'حالة الرخصة' => $trip->boat?->license_status,
                    'انتهاء الرخصة' => $trip->boat?->license_expiry?->format('Y-m-d'),
                    'منطقة الترخيص' => $trip->boat?->license_area,
                ],
            ],
            'catch' => $trip->catchRecords->map(fn ($record) => [
                'species' => $record->species?->name_ar,
                'name_sci' => $record->species?->name_sci,
                'total_kg' => round((float) $record->quantity_kg, 2),
                'captain_kg' => $record->captain_kg !== null ? round((float) $record->captain_kg, 2) : null,
                'counted_kg' => $record->counted_kg !== null ? round((float) $record->counted_kg, 2) : null,
                'captain_notes' => $record->captain_notes,
                'counter_notes' => $record->counter_notes,
                'verified' => (bool) $record->verified,
                'added_by' => $record->addedBy?->name,
                'recorded_at' => $record->recorded_at?->toDateString(),
            ])->values(),
            'totals' => [
                'species' => $trip->catchRecords->count(),
                'captain_kg' => $trip->captain_input_kg !== null ? round((float) $trip->captain_input_kg, 2) : null,
                'counted_kg' => $trip->actual_weight_kg !== null ? round((float) $trip->actual_weight_kg, 2) : null,
                'diff_kg' => $trip->diff_kg !== null ? round((float) $trip->diff_kg, 2) : null,
                'approved_kg' => $trip->approved_kg !== null ? round((float) $trip->approved_kg, 2) : null,
            ],
        ];
    }

    private function at(?object $moment): ?string
    {
        return $moment?->format('Y-m-d H:i');
    }
}
