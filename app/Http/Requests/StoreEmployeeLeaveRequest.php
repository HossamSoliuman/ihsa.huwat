<?php

namespace App\Http\Requests;

use App\Models\Leave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreEmployeeLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Leave::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $employee = $this->user()?->employee()->first();

            if ($employee !== null && $this->filled(['start_date', 'end_date'])
                && $employee->leaves()->whereIn('status', ['pending', 'approved'])
                    ->whereDate('start_date', '<=', $this->input('end_date'))
                    ->whereDate('end_date', '>=', $this->input('start_date'))->exists()) {
                $validator->errors()->add('start_date', 'يوجد طلب إجازة قائم يتداخل مع هذه الفترة.');
            }
        }];
    }
}
