<?php

namespace App\Http\Requests;

use App\Models\UserScope;
use Illuminate\Validation\Rule;

class FilterInformationModeratorsRequest extends ManageInformationModeratorRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:150'],
            'scope_type' => ['nullable', Rule::in(array_keys(UserScope::TYPES))],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'q' => 'البحث',
            'scope_type' => 'مستوى النطاق',
            'status' => 'الحالة',
        ];
    }
}
