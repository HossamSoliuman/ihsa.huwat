<?php

namespace App\Http\Requests\Owner;

use App\Models\FleetDocument;
use Illuminate\Validation\Rule;

class DocumentRequest extends OwnerRequest
{
    public function rules(): array
    {
        // الحائز قارب للمالك أو فرد من طاقمه (سجل صياد يملكه).
        $holderTable = $this->input('holder_type') === 'crew' ? 'fishers' : 'boats';

        return [
            'holder_type' => ['required', Rule::in(array_keys(FleetDocument::HOLDERS))],
            'holder_id' => ['required', 'integer', $this->owned($holderTable)],
            'document_type_id' => ['required', $this->lookup('document_types')],
            'number' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'remove_attachment' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['holder_id' => 'صاحب الوثيقة', 'document_type_id' => 'نوع الوثيقة', 'expiry_date' => 'تاريخ الانتهاء', 'issue_date' => 'تاريخ الإصدار', 'attachment' => 'المرفق'];
    }
}
