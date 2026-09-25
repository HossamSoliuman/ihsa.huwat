<?php

namespace App\Http\Requests\Dalal;

/**
 * عمالة الدكة كاملةً — التطبيق يرسل القائمة كلها فتستبدل ما قبلها.
 */
class WorkersRequest extends DalalRequest
{
    public function rules(): array
    {
        return [
            'workers' => ['present', 'array'],
            'workers.*.dalal_worker_type_id' => ['required', $this->lookup('dalal_worker_types')],
            'workers.*.nationality' => ['nullable', 'string', 'max:100'],
            'workers.*.count' => ['required', 'integer', 'min:1', 'max:500'],
            'workers.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
