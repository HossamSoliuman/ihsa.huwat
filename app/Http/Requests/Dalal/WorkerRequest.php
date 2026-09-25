<?php

namespace App\Http\Requests\Dalal;

/**
 * سطر من عمالة الدكة — الويب يضيف سطرًا سطرًا.
 */
class WorkerRequest extends DalalRequest
{
    public function rules(): array
    {
        return [
            'dalal_worker_type_id' => ['required', $this->lookup('dalal_worker_types')],
            'nationality' => ['nullable', 'string', 'max:100'],
            'count' => ['required', 'integer', 'min:1', 'max:500'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['dalal_worker_type_id' => 'نوع العامل', 'count' => 'العدد'];
    }
}
