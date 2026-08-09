<?php

namespace App\Http\Requests;

use App\Http\Controllers\Dashboard\HumanResourcesSettingsController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManageHumanResourcesSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-human-resources-settings');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'section' => ['nullable', Rule::in(array_keys(HumanResourcesSettingsController::SECTIONS))],
        ];
    }
}
