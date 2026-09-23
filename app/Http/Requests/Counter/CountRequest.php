<?php

namespace App\Http\Requests\Counter;

use Illuminate\Validation\Rule;

/**
 * عدّ المصيد: سطر لكل صنف بوزنه المعدود، ومربّع "فحص الكمية"، وملاحظة
 * العدّاد عليه. الصنف الذي لم يعلنه الكابتن يُقبل هنا أيضًا فيُضاف سطرًا
 * جديدًا ("إضافة صنف إن وجد")، والوزن صفر مقبول لصنف أُعلن ولم يُوجد.
 */
class CountRequest extends CounterRequest
{
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.species_id' => ['required', Rule::exists('species', 'id')],
            'items.*.weight_kg' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.verified' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * الخدمة تقرأ الأوزان مفهرسةً بالصنف؛ الصنف المكرّر يغلب آخرُه.
     *
     * @return array<int, array{weight_kg: float, notes: ?string, verified: bool}>
     */
    public function counted(): array
    {
        $counted = [];

        foreach ($this->validated('items') as $item) {
            $counted[(int) $item['species_id']] = [
                'weight_kg' => (float) $item['weight_kg'],
                'notes' => $item['notes'] ?? null,
                'verified' => (bool) ($item['verified'] ?? true),
            ];
        }

        return $counted;
    }

    public function messages(): array
    {
        return [
            'items.required' => 'أضف صنفًا واحدًا على الأقل.',
            'items.*.species_id.required' => 'اختر نوع السمك.',
            'items.*.weight_kg.required' => 'أدخل الوزن المعدود.',
            'items.*.weight_kg.min' => 'الوزن لا يكون سالبًا.',
        ];
    }
}
