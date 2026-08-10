<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('salary_components')->updateOrInsert(
            ['code' => 'basic'],
            [
                'name_ar' => 'الراتب الأساسي',
                'component_type' => 'earning',
                'calculation_type' => 'fixed',
                'is_basic' => true,
                'sort_order' => 10,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        $basicComponentId = (int) DB::table('salary_components')->where('code', 'basic')->value('id');

        DB::table('employees')->orderBy('id')->get()->each(function (object $employee) use ($basicComponentId): void {
            DB::table('employee_salary_components')->insert([
                'employee_id' => $employee->id,
                'salary_component_id' => $basicComponentId,
                'amount' => $employee->base_salary,
                'effective_from' => $employee->hire_date ?? today()->startOfMonth()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        DB::table('employee_salary_components')
            ->whereIn('salary_component_id', DB::table('salary_components')->where('code', 'basic')->select('id'))
            ->whereNull('created_by')
            ->delete();
    }
};
