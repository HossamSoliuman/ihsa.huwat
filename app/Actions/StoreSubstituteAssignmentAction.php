<?php

namespace App\Actions;

use App\Models\EmployeeAssignment;

class StoreSubstituteAssignmentAction
{
    public function execute(array $attributes): EmployeeAssignment
    {
        return EmployeeAssignment::query()->create([
            'employee_id' => $attributes['employee_id'],
            'port_id' => $attributes['port_id'],
            'shift_id' => $attributes['shift_id'],
            'assignment_date' => $attributes['date'],
            'is_temporary' => true,
        ]);
    }
}
