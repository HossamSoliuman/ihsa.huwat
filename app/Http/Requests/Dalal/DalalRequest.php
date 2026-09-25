<?php

namespace App\Http\Requests\Dalal;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * أصل طلبات بوابة الدلال — الويب والـAPI يتحققان بالقواعد نفسها. الدخول
 * محكوم بالوسيط (panel:dalal / api.role:dalal) فالتفويض هنا دائمًا صحيح،
 * والقواعد تقيّد المعرّفات بما يخصّ الدلال نفسه.
 */
abstract class DalalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function dalal(): User
    {
        return $this->user();
    }

    protected function lookup(string $table): Exists
    {
        return Rule::exists($table, 'id')->where('active', true);
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge(['phone' => User::normalizePhone($this->input('phone'))]);
        }
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'رقم الجوال يجب أن يكون بصيغة 05XXXXXXXX.',
            'items.required' => 'أضف صنفًا واحدًا على الأقل.',
            'items.*.species_id.required' => 'اختر نوع السمك.',
            'items.*.weight_kg.min' => 'الوزن يجب أن يكون أكبر من صفر.',
            'items.*.price_per_kg.min' => 'سعر الكيلو يجب أن يكون أكبر من صفر.',
        ];
    }
}
