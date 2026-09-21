<?php

namespace App\Services\Captain;

use App\Models\AppNotification;
use App\Models\CatchRecord;
use App\Models\Trip;
use App\Models\User;

/**
 * أرقام رئيسة الكابتن — الشاشة الأولى في التطبيق (انطلق برحلتك القادمة /
 * الرحلات النشطة / قائمة الرحلات) تُقرأ في الويب وفي التطبيق من المكان نفسه.
 */
class CaptainDashboard
{
    public function for(User $captain): array
    {
        $trips = Trip::forCaptain($captain);

        return [
            'kpis' => [
                'pending' => (clone $trips)->awaitingCaptain()->count(),
                'active' => (clone $trips)->activeForCaptain()->count(),
                'awaiting_count' => (clone $trips)->whereIn('status', [Trip::RETURNED, Trip::AWAITING_COUNT, Trip::COUNTING])->count(),
                'completed' => (clone $trips)->whereIn('status', [Trip::AWAITING_APPROVAL, Trip::APPROVED])->count(),
                'cancelled' => (clone $trips)->where('status', Trip::CANCELLED)->count(),
                'total' => (clone $trips)->count(),
                'catch_kg' => round((float) CatchRecord::whereIn('trip_id', (clone $trips)->select('id'))->sum('captain_kg'), 2),
                'unread_notifications' => AppNotification::forUser($captain)->unread()->count(),
            ],
            'pending_trips' => (clone $trips)->awaitingCaptain()->with(['boat', 'departurePort', 'tripType'])->orderBy('departure_time')->orderBy('id')->get(),
            'active_trips' => (clone $trips)->activeForCaptain()->with(['boat', 'departurePort', 'tripType'])->orderByDesc('started_at')->get(),
            'recent_trips' => (clone $trips)->with(['boat', 'departurePort'])->orderByDesc('id')->limit(6)->get(),
        ];
    }
}
