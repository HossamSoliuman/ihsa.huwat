<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesEnteredValues;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared base for every fish market screen: the same desk that maintains the reference
 * lists owns the markets, their shops and stalls, their workers and their brokers, so the
 * one gate covers all of them. Subclasses that carry no payload — the delete actions —
 * need nothing beyond the authorisation check.
 */
class ManageFishMarketRequest extends FormRequest
{
    use NormalizesEnteredValues;

    public function authorize(): bool
    {
        return $this->user()->can('manage-information-lookups');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
