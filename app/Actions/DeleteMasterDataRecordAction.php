<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteMasterDataRecordAction
{
    /**
     * @param  array<string, string>  $dependencies  table => foreign key
     */
    public function execute(Model $record, array $dependencies, string $label): void
    {
        foreach ($dependencies as $table => $foreignKey) {
            if (DB::table($table)->where($foreignKey, $record->getKey())->exists()) {
                throw ValidationException::withMessages([
                    'delete' => "لا يمكن حذف {$label} لارتباطه ببيانات تشغيلية.",
                ]);
            }
        }

        $record->delete();
    }
}
