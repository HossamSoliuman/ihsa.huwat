<?php

namespace App\Http\Requests;

use App\Http\Controllers\Dashboard\HumanResourcesSettingsController;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEmploymentLookupOptionRequest extends ManageHumanResourcesSettingsRequest
{
    protected function prepareForValidation(): void
    {
        $code = Str::of((string) $this->input('name'))->slug('_');

        $this->merge([
            'code' => $code->isEmpty() ? 'option_'.Str::lower(Str::random(8)) : $code->limit(60, '')->toString(),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $model = HumanResourcesSettingsController::optionModel((string) $this->route('list'));

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique($model, 'name')],
            'code' => ['required', 'regex:/^[a-z0-9_]{2,60}$/', Rule::unique($model, 'code')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.unique' => 'هذا الخيار مضاف بالفعل في هذه القائمة.',
            'code.unique' => 'هذا الخيار مضاف بالفعل في هذه القائمة.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'اسم الخيار'];
    }
}
