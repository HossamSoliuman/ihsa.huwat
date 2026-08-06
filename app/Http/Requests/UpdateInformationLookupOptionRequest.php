<?php

namespace App\Http\Requests;

use App\Models\LookupList;
use Illuminate\Validation\Rule;

class UpdateInformationLookupOptionRequest extends ManageInformationLookupRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $list = LookupList::resolve((string) $this->route('list'));

        return [
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique($list, 'name')->ignore($this->route('option')),
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
