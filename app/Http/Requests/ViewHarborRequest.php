<?php

namespace App\Http\Requests;

use App\Models\Port;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ViewHarborRequest extends FormRequest
{
    public function authorize(): bool
    {
        $port = $this->route('port');

        return $port instanceof Port ? $this->user()->can('view', $port) : $this->user()->can('viewAny', Port::class);
    }

    public function rules(): array
    {
        return ['tab' => ['nullable', Rule::in(['overview', 'boats', 'workers', 'licenses', 'violations'])]];
    }
}
