<?php

namespace App\Http\Requests;

class UpdateEmploymentJobRequest extends StoreEmploymentJobRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('job'));
    }
}
