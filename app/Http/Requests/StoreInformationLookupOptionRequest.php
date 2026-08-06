<?php

namespace App\Http\Requests;

use App\Models\LookupList;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreInformationLookupOptionRequest extends ManageInformationLookupRequest
{
    protected function prepareForValidation(): void
    {
        /**
         * Stored submissions keep the code, not the Arabic name, so every option needs a
         * stable latin one. Arabic names slugify to nothing, hence the random fallback.
         * The desk never asks for it — it is derived here and kept out of sight.
         */
        $code = Str::of((string) $this->input('name'))->slug('_');

        $this->merge([
            'code' => $code->isEmpty() ? 'option_'.Str::lower(Str::random(8)) : $code->limit(60, '')->toString(),
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $list = LookupList::resolve((string) $this->route('list'));

        return [
            'name' => ['required', 'string', 'max:150', Rule::unique($list, 'name')],
            'code' => ['required', 'regex:/^[a-z0-9_]{2,60}$/', Rule::unique($list, 'code')],
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
