<?php

namespace App\Http\Requests;

class DeleteHarborRecordRequest extends ViewHarborRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('port'));
    }

    public function rules(): array
    {
        return [];
    }
}
