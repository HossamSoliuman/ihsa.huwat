<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\NormalizesEnteredValues;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Shared base for the moderator accounts. Creating a login and deciding what it reaches is
 * the desk's alone — a moderator never opens this screen, and so can never widen itself.
 */
class ManageInformationModeratorRequest extends FormRequest
{
    use NormalizesEnteredValues;

    public function authorize(): bool
    {
        return $this->user()->can('manage-information-moderators');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }
}
