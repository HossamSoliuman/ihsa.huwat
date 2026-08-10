<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Models\UserScope;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

class StoreInformationModeratorRequest extends ManageInformationModeratorRequest
{
    /** The table each scope level draws its records from. */
    private const SCOPE_TABLES = [
        UserScope::TYPE_REGION => 'regions',
        UserScope::TYPE_GOVERNORATE => 'governorates',
        UserScope::TYPE_PORT => 'ports',
        UserScope::TYPE_MARKET => 'fish_markets',
    ];

    /**
     * The page carries a checkbox list per level, each filed under its own key, so the lists
     * the desk did not choose submit alongside the chosen one without confusing it — and the
     * form still works with no script narrowing the page to one list.
     */
    protected function prepareForValidation(): void
    {
        $chosen = data_get($this->input('scope_ids'), (string) $this->input('scope_type'), []);

        $this->merge([
            'full_name' => $this->normalizeText($this->input('full_name')),
            'username' => mb_strtolower($this->normalizeCode($this->input('username'))),
            'email' => mb_strtolower($this->normalizeText($this->input('email'))) ?: null,
            'phone' => $this->normalizePhone($this->input('phone')),
            'national_id' => $this->normalizeDigits($this->input('national_id')),
            'is_active' => $this->boolean('is_active'),
            'scope_ids' => is_array($chosen) ? array_values($chosen) : [],
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:150'],
            'username' => ['required', 'string', 'min:4', 'max:100', 'regex:/\A[a-z0-9._-]+\z/', $this->usernameIsUnique()],
            'email' => ['nullable', 'email:rfc', 'max:150', $this->emailIsUnique()],
            'phone' => ['required', 'regex:/^\+?[0-9]{8,15}$/'],
            'national_id' => ['required', 'regex:/^[12][0-9]{9}$/'],
            'password' => [...$this->passwordPresence(), 'max:200', Password::min(10)],
            'is_active' => ['required', 'boolean'],
            'scope_type' => ['required', Rule::in(array_keys(UserScope::TYPES))],
            /** One level per account, so the records are all drawn from the one table. */
            'scope_ids' => ['required', 'array', 'min:1', 'max:200'],
            'scope_ids.*' => ['integer', 'distinct', ...$this->scopeRecordExists()],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            '*.required' => 'هذا الحقل مطلوب.',
            'username.regex' => 'اسم المستخدم بحروف لاتينية صغيرة وأرقام و . _ - فقط.',
            'username.unique' => 'اسم المستخدم مستخدم بالفعل.',
            'email.unique' => 'البريد الإلكتروني مسجل على حساب آخر.',
            'phone.regex' => 'أدخل رقم جوال صحيحاً، مثل 05xxxxxxxx.',
            'national_id.regex' => 'أدخل رقم هوية أو إقامة من 10 أرقام يبدأ بـ 1 أو 2.',
            'scope_ids.required' => 'اختر سجلاً واحداً على الأقل ضمن نطاق المشرف.',
            'scope_ids.*.exists' => 'أحد السجلات المختارة غير معروف.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'full_name' => 'الاسم',
            'username' => 'اسم المستخدم',
            'email' => 'البريد الإلكتروني',
            'phone' => 'رقم الجوال',
            'national_id' => 'رقم الهوية',
            'password' => 'كلمة المرور',
            'is_active' => 'حالة الحساب',
            'scope_type' => 'مستوى النطاق',
            'scope_ids' => 'سجلات النطاق',
        ];
    }

    /** @return list<string> */
    protected function passwordPresence(): array
    {
        return ['required'];
    }

    protected function usernameIsUnique(): Unique
    {
        return Rule::unique(User::class, 'username');
    }

    protected function emailIsUnique(): Unique
    {
        return Rule::unique(User::class, 'email');
    }

    /**
     * The level decides which table the records are checked against. A level that failed its
     * own rule leaves nothing to check them with, and its error is the one worth showing.
     *
     * @return list<mixed>
     */
    private function scopeRecordExists(): array
    {
        $table = self::SCOPE_TABLES[$this->input('scope_type')] ?? null;

        return $table === null ? [] : [Rule::exists($table, 'id')];
    }
}
