<?php

namespace App\Models;

class FishingSeason extends BaseModel
{
    public const MONTHS = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'ban_start_date' => 'date',
        'ban_end_date' => 'date',
        'decision_date' => 'date',
    ];

    public function licenses()
    {
        return $this->hasMany(SeasonLicense::class);
    }

    public function isOpenNow(): bool
    {
        $cm = now()->month;
        $start = (int) $this->start_month;
        $end = (int) $this->end_month;

        if (! $start || ! $end || $this->status === 'موقوف مؤقتاً') {
            return false;
        }

        return $start <= $end
            ? $cm >= $start && $cm <= $end
            : $cm >= $start || $cm <= $end;
    }

    public function nextWindow(): array
    {
        $cm = now()->month;
        $start = (int) $this->start_month;
        $end = (int) $this->end_month;

        if ($this->isOpenNow()) {
            $months = $start <= $end ? $end - $cm : (($end - $cm) + 12) % 12;

            return ['kind' => 'open', 'months' => $months];
        }

        return ['kind' => 'closed', 'months' => ($start - $cm + 12) % 12];
    }
}