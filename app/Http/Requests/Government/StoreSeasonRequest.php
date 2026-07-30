<?php

namespace App\Http\Requests\Government;

use App\Models\Season;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSeasonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user('government')?->role?->code, config('government.allowed_roles'), true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'status' => ['required', Rule::in([
                Season::STATUS_UPCOMING,
                Season::STATUS_ACTIVE,
                Season::STATUS_CLOSED,
            ])],
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'fishing_tools' => ['required', 'array', 'min:1'],
            'fishing_tools.*' => ['required', 'string', Rule::in(config('government.fishing_tool_options'))],
            'licenses_count' => ['required', 'integer', 'min:0'],
            'minimum_size' => ['nullable', 'numeric', 'min:0'],
            'maximum_size' => ['nullable', 'numeric', 'min:0'],
            'restrictions' => ['required', 'string', 'max:2000'],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled(['minimum_size', 'maximum_size']) && $this->float('minimum_size') > $this->float('maximum_size')) {
                    $validator->errors()->add('maximum_size', 'يجب أن يكون الحد الأعلى أكبر من الحد الأدنى أو مساويًا له.');
                }
            },
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'اسم الموسم',
            'status' => 'الحالة',
            'region_id' => 'المنطقة',
            'start_date' => 'تاريخ البداية',
            'end_date' => 'تاريخ النهاية',
            'fishing_tools' => 'أدوات الصيد',
            'licenses_count' => 'عدد الرخص الموسمية',
            'minimum_size' => 'الحد الأدنى للقياس',
            'maximum_size' => 'الحد الأعلى للقياس',
            'restrictions' => 'القيود',
        ];
    }
}
