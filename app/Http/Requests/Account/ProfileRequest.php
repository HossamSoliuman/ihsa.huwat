<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * تعديل الملف الشخصي (الاسم، البريد، اللغة) — شاشة واحدة لكل الأدوار في
 * الويب والتطبيق. الجوال والدور لا يغيّرهما صاحب الحساب.
 */
class ProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user())],
            'locale' => ['sometimes', 'required', Rule::in(['ar', 'en'])],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'الاسم', 'email' => 'البريد', 'locale' => 'اللغة'];
    }
}
