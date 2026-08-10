@use('App\Models\UserScope')

@props([
    'moderator' => null,
    'regions' => [],
    'governorates' => [],
    'ports' => [],
    'markets' => [],
])

@php
    /**
     * One form for both جديد and تعديل. Every level's list of records is in the markup at
     * once, each filed under its own key, so the page narrows to the level in play with no
     * round trip and the form still submits correctly with no script at all.
     */
    $isEdit = $moderator !== null;
    $idPrefix = $isEdit ? 'moderator-'.$moderator->id : 'moderator-new';

    $assigned = $isEdit ? $moderator->assignedScopes : collect();
    $scopeType = old('scope_type', $assigned->first()?->scope_type ?? UserScope::TYPE_PORT);
    /** The request flattens the chosen level's boxes into `scope_ids`, so only that list reads them back. */
    $checkedIds = array_map('intval', (array) old('scope_ids', $assigned->pluck('scope_id')->all()));

    $isActive = $errors->any()
        ? (bool) old('is_active')
        : (bool) ($moderator->is_active ?? true);

    $records = [
        UserScope::TYPE_REGION => ['options' => $regions, 'parent' => null],
        UserScope::TYPE_GOVERNORATE => ['options' => $governorates, 'parent' => 'region'],
        UserScope::TYPE_PORT => ['options' => $ports, 'parent' => 'governorate'],
        UserScope::TYPE_MARKET => ['options' => $markets, 'parent' => 'governorate'],
    ];

    $everyParentLabels = [
        UserScope::TYPE_REGION => 'كل المناطق',
        UserScope::TYPE_GOVERNORATE => 'كل المحافظات',
    ];
@endphp

<form class="info-admin-form" method="post"
      action="{{ $isEdit ? route('information.admin.moderators.update', $moderator) : route('information.admin.moderators.store') }}">
    @csrf
    @if ($isEdit)
        @method('PATCH')
    @endif

    <div class="info-field-grid info-field-grid-three">
        <div class="info-field">
            <label for="{{ $idPrefix }}_full_name">الاسم <b aria-hidden="true">*</b></label>
            <input id="{{ $idPrefix }}_full_name" name="full_name" value="{{ old('full_name', $moderator->full_name ?? '') }}"
                   required minlength="3" maxlength="150" placeholder="مثال: سالم أحمد الزهراني"
                   @error('full_name') aria-invalid="true" aria-describedby="{{ $idPrefix }}_full_name_error" @enderror>
            @error('full_name')<small id="{{ $idPrefix }}_full_name_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        <div class="info-field">
            <label for="{{ $idPrefix }}_phone">رقم الجوال <b aria-hidden="true">*</b></label>
            <input id="{{ $idPrefix }}_phone" name="phone" type="tel" dir="ltr" inputmode="tel"
                   value="{{ old('phone', $moderator->phone ?? '') }}" required maxlength="30" placeholder="05xxxxxxxx"
                   @error('phone') aria-invalid="true" aria-describedby="{{ $idPrefix }}_phone_error" @enderror>
            @error('phone')<small id="{{ $idPrefix }}_phone_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        <div class="info-field">
            <label for="{{ $idPrefix }}_national_id">رقم الهوية <b aria-hidden="true">*</b></label>
            <input id="{{ $idPrefix }}_national_id" name="national_id" dir="ltr" inputmode="numeric"
                   value="{{ old('national_id', $moderator->national_id ?? '') }}" required maxlength="20" placeholder="1xxxxxxxxx"
                   @error('national_id') aria-invalid="true" aria-describedby="{{ $idPrefix }}_national_id_error" @enderror>
            @error('national_id')<small id="{{ $idPrefix }}_national_id_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>
    </div>

    <div class="info-field-grid info-field-grid-three">
        <div class="info-field">
            <label for="{{ $idPrefix }}_username">اسم المستخدم <b aria-hidden="true">*</b></label>
            <input id="{{ $idPrefix }}_username" name="username" dir="ltr" autocomplete="off"
                   value="{{ old('username', $moderator->username ?? '') }}" required minlength="4" maxlength="100"
                   placeholder="salem.alzahrani"
                   @error('username') aria-invalid="true" aria-describedby="{{ $idPrefix }}_username_error" @enderror>
            @error('username')<small id="{{ $idPrefix }}_username_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        <div class="info-field">
            <label for="{{ $idPrefix }}_password">كلمة المرور @unless ($isEdit)<b aria-hidden="true">*</b>@endunless</label>
            <input id="{{ $idPrefix }}_password" name="password" type="password" dir="ltr" autocomplete="new-password"
                   @required(! $isEdit) minlength="10" maxlength="200"
                   placeholder="{{ $isEdit ? 'اتركها فارغة للإبقاء على الحالية' : '10 أحرف على الأقل' }}"
                   @error('password') aria-invalid="true" aria-describedby="{{ $idPrefix }}_password_error" @enderror>
            @error('password')<small id="{{ $idPrefix }}_password_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        <div class="info-field">
            <label for="{{ $idPrefix }}_email">البريد الإلكتروني</label>
            <input id="{{ $idPrefix }}_email" name="email" type="email" dir="ltr"
                   value="{{ old('email', $moderator->email ?? '') }}" maxlength="150" placeholder="name@hawat.sa"
                   @error('email') aria-invalid="true" aria-describedby="{{ $idPrefix }}_email_error" @enderror>
            @error('email')<small id="{{ $idPrefix }}_email_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>
    </div>

    <div class="info-field-grid info-field-grid-three">
        <div class="info-field">
            <label for="{{ $idPrefix }}_scope_type">مستوى النطاق <b aria-hidden="true">*</b></label>
            <select id="{{ $idPrefix }}_scope_type" name="scope_type" required data-scope-level
                    @error('scope_type') aria-invalid="true" aria-describedby="{{ $idPrefix }}_scope_type_error" @enderror>
                @foreach (UserScope::TYPES as $type => $label)
                    <option value="{{ $type }}" @selected($scopeType === $type)>{{ $label }}</option>
                @endforeach
            </select>
            @error('scope_type')<small id="{{ $idPrefix }}_scope_type_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>
    </div>

    <div data-scope-picker>
        @foreach ($records as $type => $level)
            @php
                $parent = $level['parent'];
                /** The parents in play, so a long list narrows to one محافظة before anything is ticked. */
                $parents = $parent === null
                    ? collect()
                    : collect($level['options'])->pluck($parent)->filter()->unique('id')->sortBy('name')->values();
                $selectedCount = $scopeType === $type
                    ? collect($level['options'])->filter(fn ($option): bool => in_array((int) $option->id, $checkedIds, true))->count()
                    : 0;
            @endphp

            <div class="info-scope-list" data-scope-list="{{ $type }}" @if ($scopeType !== $type) hidden @endif>
                <div class="info-scope-list-head">
                    <label class="sr-only" for="{{ $idPrefix }}_search_{{ $type }}">تصفية {{ UserScope::TYPES[$type] }}</label>
                    <input id="{{ $idPrefix }}_search_{{ $type }}" type="search" data-scope-search
                           placeholder="ابحث في {{ UserScope::TYPES[$type] }}">

                    @if ($parents->isNotEmpty())
                        <label class="sr-only" for="{{ $idPrefix }}_parent_{{ $type }}">{{ UserScope::TYPES[$parent] }}</label>
                        <select id="{{ $idPrefix }}_parent_{{ $type }}" data-scope-parent-filter>
                            <option value="">{{ $everyParentLabels[$parent] }}</option>
                            @foreach ($parents as $record)
                                <option value="{{ $record->id }}">{{ $record->name }}</option>
                            @endforeach
                        </select>
                    @endif

                    <span class="info-scope-count" data-scope-count>{{ $selectedCount }} محدد</span>

                    <button class="info-button info-button-secondary" type="button" data-scope-select-all>تحديد الكل</button>
                    <button class="info-button info-button-secondary" type="button" data-scope-selected-only
                            aria-pressed="false">المحدد فقط</button>
                    <button class="info-button info-button-secondary" type="button" data-scope-clear>إلغاء التحديد</button>
                </div>

                <div class="info-scope-options">
                    @forelse ($level['options'] as $option)
                        <label class="info-lookup-check"
                               @if ($parent !== null) data-scope-parent="{{ $option->{$parent}?->id }}" @endif>
                            <input type="checkbox" name="scope_ids[{{ $type }}][]" value="{{ $option->id }}"
                                   @checked($scopeType === $type && in_array((int) $option->id, $checkedIds, true))>
                            <span>
                                {{ $option->name }}
                                @if ($parent !== null && $option->{$parent} !== null)
                                    <small>{{ $option->{$parent}->name }}</small>
                                @endif
                            </span>
                        </label>
                    @empty
                        <p class="info-admin-empty">لا توجد سجلات على هذا المستوى.</p>
                    @endforelse

                    <p class="info-admin-empty" data-scope-empty hidden>لا توجد نتائج مطابقة.</p>
                </div>
            </div>
        @endforeach

        @error('scope_ids')<small class="info-field-error">{{ $message }}</small>@enderror
        @error('scope_ids.*')<small class="info-field-error">{{ $message }}</small>@enderror
    </div>

    <div class="info-admin-form-actions">
        <label class="info-lookup-check">
            <input type="checkbox" name="is_active" value="1" @checked($isActive)>
            <span>حساب نشط</span>
        </label>

        <button class="info-button info-button-primary" type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg>
            <span>{{ $isEdit ? 'حفظ التعديلات' : 'إنشاء الحساب' }}</span>
        </button>
    </div>
</form>
