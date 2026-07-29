<?php

namespace App\Http\Requests;

use App\Models\Boat;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator;

class StoreHarborViolationRequest extends DeleteHarborRecordRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['remove_attachment' => $this->boolean('remove_attachment')]);
    }

    public function rules(): array
    {
        return [
            'violation_number' => ['required', 'string', 'max:80', Rule::unique('harbor_violations')->ignore($this->route('violation'))],
            'violation_type' => ['required', 'string', 'max:120'], 'violation_description' => ['nullable', 'string'],
            'violation_date' => ['required', 'date'], 'boat_id' => ['nullable', 'integer', 'exists:boats,id'],
            'boat_owner_name' => ['nullable', 'string', 'max:190'], 'fine_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'violation_status' => ['required', Rule::in(['open', 'paid', 'appealed', 'closed'])],
            'attachment' => ['nullable', File::types(['pdf', 'jpg', 'jpeg', 'png', 'webp'])->max(10 * 1024)],
            'remove_attachment' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled('boat_id') && ! Boat::query()->whereKey($this->integer('boat_id'))->where('home_port_id', $this->route('port')->id)->exists()) {
                $validator->errors()->add('boat_id', 'القارب المحدد غير مسجل في هذا المرفأ.');
            }
        }];
    }
}
