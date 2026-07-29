<?php

namespace App\Actions;

use App\Models\Port;
use Illuminate\Support\Facades\DB;

class UpdateHarborCapacitiesAction
{
    public function execute(Port $port, array $capacities): void
    {
        DB::transaction(function () use ($port, $capacities): void {
            foreach ($capacities as $boatType => $attributes) {
                $port->capacities()->updateOrCreate(['boat_type' => $boatType], $attributes);
            }
        });
    }
}
