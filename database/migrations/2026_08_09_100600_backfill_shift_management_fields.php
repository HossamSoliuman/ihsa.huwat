<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shifts')
            ->orderBy('id')
            ->get(['id', 'name', 'start_time', 'end_time'])
            ->each(function (object $shift): void {
                $code = (string) $shift->name;

                DB::table('shifts')->where('id', $shift->id)->update([
                    'code' => $code,
                    'name' => (string) config('attendance.shifts.'.$code, $code),
                    'crosses_midnight' => (string) $shift->end_time <= (string) $shift->start_time,
                ]);
            });
    }

    public function down(): void
    {
        DB::table('shifts')
            ->whereNotNull('code')
            ->orderBy('id')
            ->get(['id', 'code'])
            ->each(fn (object $shift) => DB::table('shifts')->where('id', $shift->id)->update([
                'name' => $shift->code,
            ]));
    }
};
