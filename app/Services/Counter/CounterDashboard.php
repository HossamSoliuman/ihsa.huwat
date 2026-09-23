<?php

namespace App\Services\Counter;

use App\Models\AppNotification;
use App\Models\Trip;
use App\Models\User;

/**
 * أرقام رئيسة العدّاد — الشاشة الأولى في التطبيق (الرحلات النشطة / قائمة
 * الرحلات / رحلات بحاجة لموافقتك) تُقرأ في الويب وفي التطبيق من المكان نفسه.
 *
 * الطابور طابور ميناء لا طابور شخص: كل عدّادي الميناء يرون ما عاد إليه،
 * ومن يستلم الرحلة أوّلًا هو من يعدّها.
 */
class CounterDashboard
{
    public function for(User $counter): array
    {
        $trips = Trip::forCounter($counter);
        $port = $counter->statisticsOfficer?->port;

        return [
            'port' => $port,
            'kpis' => [
                'awaiting' => (clone $trips)->awaitingCounter()->count(),
                'counting' => (clone $trips)->underCount()->count(),
                'counted_today' => (clone $trips)->whereDate('counted_at', today())->count(),
                'counted' => (clone $trips)->whereNotNull('counted_at')->count(),
                'declared_kg' => round((float) (clone $trips)->awaitingCounter()->sum('captain_input_kg'), 2),
                'counted_kg' => round((float) (clone $trips)->whereNotNull('counted_at')->sum('actual_weight_kg'), 2),
                // فرق العدّ التراكمي — ما بين ما أعلنه الكباتن وما قِيس فعلًا.
                'diff_kg' => round((float) (clone $trips)->whereNotNull('counted_at')->sum('diff_kg'), 2),
                'unread_notifications' => AppNotification::forUser($counter)->unread()->count(),
            ],
            'awaiting_trips' => (clone $trips)->awaitingCounter()->with(['boat', 'captain', 'returnPort', 'departurePort', 'tripType'])
                ->orderBy('catch_submitted_at')->orderBy('id')->get(),
            'counting_trips' => (clone $trips)->underCount()->with(['boat', 'captain', 'returnPort', 'departurePort', 'tripType'])
                ->orderBy('received_at')->get(),
            'recent_trips' => (clone $trips)->whereNotNull('counted_at')->with(['boat', 'owner', 'returnPort', 'departurePort'])
                ->orderByDesc('counted_at')->limit(6)->get(),
        ];
    }
}
