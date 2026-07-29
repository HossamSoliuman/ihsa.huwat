<?php

namespace App\Actions;

use App\Models\EmployeeAssignment;
use Illuminate\Support\Facades\DB;

class AssignEmployeeAction
{
    public function execute(array $attributes): EmployeeAssignment
    {
        return DB::transaction(function () use ($attributes): EmployeeAssignment {
            $assignment = EmployeeAssignment::query()->where('employee_id', $attributes['employee_id'])
                ->whereDate('assignment_date', $attributes['assignment_date'])->lockForUpdate()->first();
            $values = ['port_id' => $attributes['port_id'], 'shift_id' => $attributes['shift_id'], 'is_temporary' => false];

            if ($assignment !== null) {
                $assignment->update($values);

                return $assignment;
            }

            return EmployeeAssignment::query()->create([
                'employee_id' => $attributes['employee_id'], 'assignment_date' => $attributes['assignment_date'], ...$values,
            ]);
        });
    }
}
