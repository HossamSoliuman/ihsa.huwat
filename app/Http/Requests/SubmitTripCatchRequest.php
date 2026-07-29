<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitTripCatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recordCatch', $this->route('trip')) ?? false;
    }

    public function rules(): array
    {
        return [
            'catches' => ['required', 'array', 'min:1'],
            'catches.*.species_id' => ['required', 'integer', 'distinct', 'exists:fish_species,id'],
            'catches.*.reported_kg' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'catches.*.verified_kg' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'catches.*.boxes_count' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $hasCatch = collect($this->input('catches', []))->contains(
                fn (array $catch): bool => (float) ($catch['reported_kg'] ?? 0) > 0 || (float) ($catch['verified_kg'] ?? 0) > 0,
            );

            if (! $hasCatch) {
                $validator->errors()->add('catches', 'أدخل كمية لصنف واحد على الأقل.');
            }
        }];
    }
}
