<?php

namespace App\Http\Requests\Dalal;

class LogoRequest extends DalalRequest
{
    public function rules(): array
    {
        return [
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return ['logo' => 'الشعار'];
    }
}
