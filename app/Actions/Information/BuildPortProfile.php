<?php

namespace App\Actions\Information;

use App\Models\BoatType;
use App\Models\HarborBoatCapacity;
use App\Models\InformationSubmission;
use App\Models\Port;

/**
 * Everything the information centre shows about one port, read from the records already
 * attached to it: the berth capacity the harbour desk maintains, the boats homed there,
 * its workers, its licences and violations, and the submissions filed under it.
 */
class BuildPortProfile
{
    /** Berth capacity is kept per boat type, and these three are the ones a port declares. */
    private const BOAT_TYPES = ['large' => 'قوارب كبيرة', 'small' => 'قوارب صغيرة', 'recreational' => 'قوارب نزهة'];

    private const WORKER_TYPES = [
        'supervisor' => 'المشرفون',
        'contractor' => 'المتعاقدون',
        'fisherman' => 'الصيادون',
        'foreign_worker' => 'العمالة الأجنبية',
    ];

    /** @return array<string, mixed> */
    public function execute(Port $port): array
    {
        $port->load([
            'governorate.region',
            'capacities',
            'boats',
            'harborWorkers',
            'harborLicenses',
            'harborViolations',
        ]);

        $boatTypes = $this->boatTypes($port);

        return [
            'port' => $port,
            'boatTypes' => $boatTypes,
            'capacity' => array_sum(array_column($boatTypes, 'capacity')),
            'occupied' => array_sum(array_column($boatTypes, 'occupied')),
            'occupancy' => $this->percentage(
                array_sum(array_column($boatTypes, 'occupied')),
                array_sum(array_column($boatTypes, 'capacity')),
            ),
            'workers' => $this->workers($port),
            'activeWorkers' => $port->harborWorkers->where('employment_status', 'active')->count(),
            'validLicenses' => $port->harborLicenses->where('license_status', 'valid')->count(),
            'openViolations' => $port->harborViolations->whereIn('violation_status', ['open', 'appealed'])->count(),
            'submissions' => $this->submissions($port),
        ];
    }

    /**
     * @return list<array{code: string, label: string, capacity: int, occupied: int, available: int, disabled: int, percent: float, status: string}>
     */
    private function boatTypes(Port $port): array
    {
        /** The desk may rename a boat type, so its own list wins over the shipped label. */
        $labels = BoatType::labels();

        return collect(self::BOAT_TYPES)->map(function (string $fallback, string $code) use ($port, $labels): array {
            $capacityRecord = $port->capacities->firstWhere('boat_type', $code);
            $boats = $port->boats->where('boat_type', $code);
            $capacity = (int) ($capacityRecord?->capacity ?? 0);
            $occupied = $boats->where('harbor_status', 'occupied')->count();

            return [
                'code' => $code,
                'label' => $labels[$code] ?? $fallback,
                'capacity' => $capacity,
                'occupied' => $occupied,
                'available' => max(0, $capacity - $occupied),
                'disabled' => $boats->where('harbor_status', 'disabled')->count(),
                'percent' => $this->percentage($occupied, $capacity),
                'status' => $this->berthStatus($capacityRecord, $capacity, $occupied),
            ];
        })->values()->all();
    }

    /** @return list<array{label: string, count: int}> */
    private function workers(Port $port): array
    {
        return collect(self::WORKER_TYPES)->map(fn (string $label, string $code): array => [
            'label' => $label,
            'count' => $port->harborWorkers->where('worker_type', $code)->count(),
        ])->values()->all();
    }

    /** @return list<array{status: string, label: string, count: int}> */
    private function submissions(Port $port): array
    {
        $counts = InformationSubmission::query()
            ->where('port_id', $port->id)
            ->toBase()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return collect(InformationSubmission::STATUS_LABELS)
            ->map(fn (string $label, string $status): array => [
                'status' => $status,
                'label' => $label,
                'count' => (int) $counts->get($status, 0),
            ])
            ->values()
            ->all();
    }

    private function berthStatus(?HarborBoatCapacity $capacityRecord, int $capacity, int $occupied): string
    {
        if ($capacityRecord?->status === 'stopped') {
            return 'stopped';
        }

        return $capacity > 0 && $occupied >= $capacity ? 'full' : 'available';
    }

    private function percentage(int $part, int $whole): float
    {
        return $whole > 0 ? min(100, round(($part / $whole) * 100, 1)) : 0.0;
    }
}
