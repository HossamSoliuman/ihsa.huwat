<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('base_salary');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('base_salary', 10, 2)->default(0)->after('hire_date');
        });

        $basicComponentId = DB::table('salary_components')->where('code', 'basic')->value('id');

        if ($basicComponentId === null) {
            return;
        }

        DB::table('employees')->orderBy('id')->get()->each(function (object $employee) use ($basicComponentId): void {
            $amount = DB::table('employee_salary_components')
                ->where('employee_id', $employee->id)
                ->where('salary_component_id', $basicComponentId)
                ->latest('effective_from')
                ->value('amount');

            DB::table('employees')->where('id', $employee->id)->update(['base_salary' => $amount ?? 0]);
        });
    }
};
