<?php

namespace App\Http\Requests\Owner;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * أصل طلبات بوابة المالك — الويب والـAPI يتحققان بالقواعد نفسها. الدخول
 * محكوم بالوسيط (panel:owner / api.role:owner) فالتفويض هنا دائمًا صحيح،
 * والقواعد تقيّد المعرّفات بما يملكه المالك نفسه.
 */
abstract class OwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function owner(): User
    {
        return $this->user();
    }

    /**
     * قيد "موجود ويملكه هذا المالك" على جدول بعمود مالك.
     */
    protected function owned(string $table, string $column = 'owner_id'): Exists
    {
        return Rule::exists($table, 'id')->where($column, $this->owner()->id);
    }

    protected function lookup(string $table): Exists
    {
        return Rule::exists($table, 'id')->where('active', true);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
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
