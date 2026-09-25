<?php

namespace App\Http\Requests\Owner;

use App\Models\BoatInspection;
use Illuminate\Validation\Rule;

class InspectionRequest extends OwnerRequest
{
    public function rules(): array
    {
        return [
            'boat_id' => ['required', $this->owned('boats')],
            'inspection_date' => ['required', 'date'],
            'next_due_date' => ['nullable', 'date', 'after:inspection_date'],
            'inspector' => ['nullable', 'string', 'max:255'],
            'result' => ['required', Rule::in(BoatInspection::RESULTS)],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'remove_attachment' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['boat_id' => 'القارب', 'inspection_date' => 'تاريخ الفحص', 'next_due_date' => 'الفحص القادم', 'result' => 'النتيجة', 'attachment' => 'المرفق'];
    }
}
