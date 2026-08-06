<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteMasterDataRecordAction
{
    /**
     * @param  array<string, string>  $dependencies  table => foreign key holding the record's primary key
     * @param  list<array{0: string, 1: string, 2: string}>  $valueDependencies  table, column, and the
     *                                                                           attribute it stores, for
     *                                                                           records referenced by value
     *                                                                           rather than by id
     */
    public function execute(Model $record, array $dependencies, string $label, array $valueDependencies = []): void
    {
        foreach ($dependencies as $table => $foreignKey) {
            $this->assertUnused($table, $foreignKey, $record->getKey(), $label);
        }

        foreach ($valueDependencies as [$table, $column, $attribute]) {
            $this->assertUnused($table, $column, $record->getAttribute($attribute), $label);
        }

        $record->delete();
    }

    private function assertUnused(string $table, string $column, mixed $value, string $label): void
    {
        if (DB::table($table)->where($column, $value)->exists()) {
            throw ValidationException::withMessages([
                'delete' => "لا يمكن حذف {$label} لارتباطه ببيانات تشغيلية.",
            ]);
        }
    }
}
