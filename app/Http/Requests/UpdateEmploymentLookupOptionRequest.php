<?php

namespace App\Http\Requests;

use App\Http\Controllers\Dashboard\HumanResourcesSettingsController;
use Illuminate\Validation\Rule;

class UpdateEmploymentLookupOptionRequest extends ManageHumanResourcesSettingsRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $model = HumanResourcesSettingsController::optionModel((string) $this->route('list'));

        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique($model, 'name')->ignore($this->route('option')),
            ],
            'sort_order' => ['required', 'integer', 'between:0,9999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return ['name.unique' => 'هذا الخيار مضاف بالفعل في هذه القائمة.'];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['name' => 'اسم الخيار', 'sort_order' => 'الترتيب'];
    }
}
