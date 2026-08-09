@extends('layouts.dashboard')

@section('title', 'إعدادات الموارد البشرية')

@section('content')
<div class="employment-settings">
    <header class="employment-settings-head">
        <span class="employment-settings-index">HR / 01</span>
        <h1>إعدادات الموارد البشرية</h1>
    </header>

    <nav class="employment-settings-tabs" aria-label="أقسام إعدادات الموارد البشرية">
        @foreach ($sections as $sectionKey => $sectionLabel)
            <a href="{{ route('dashboard.hr.settings.index', ['section' => $sectionKey]) }}"
               @class(['is-active' => $section === $sectionKey])
               @if ($section === $sectionKey) aria-current="page" @endif>
                <span>{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                {{ $sectionLabel }}
            </a>
        @endforeach
    </nav>

    @if (in_array($section, ['departments', 'job_titles', 'banks'], true))
        <section class="employment-settings-card" aria-labelledby="settings-title">
            <header>
                <h2 id="settings-title">{{ $sections[$section] }}</h2>
                <span>{{ $options->where('is_active', true)->count() }} / {{ $options->count() }}</span>
            </header>

            <form class="employment-settings-create" method="post" action="{{ route('dashboard.hr.settings.options.store', $section) }}">
                @csrf
                <label>
                    <span>الاسم</span>
                    <input name="name" value="{{ old('name') }}" maxlength="150" required placeholder="اسم الخيار">
                </label>
                <button class="btn btn-primary" type="submit">إضافة</button>
            </form>

            <div class="employment-settings-table-wrap">
                <table class="employment-settings-table">
                    <thead><tr><th>الاسم</th><th>الرمز</th><th>الترتيب</th><th>الحالة</th><th><span class="sr-only">الإجراءات</span></th></tr></thead>
                    <tbody>
                    @forelse ($options as $option)
                        @php($formId = 'option-'.$option->id)
                        <tr @class(['is-retired' => ! $option->is_active])>
                            <td><label class="sr-only" for="{{ $formId }}-name">الاسم</label><input id="{{ $formId }}-name" form="{{ $formId }}" name="name" value="{{ $option->name }}" maxlength="150" required></td>
                            <td><code dir="ltr">{{ $option->code }}</code></td>
                            <td><label class="sr-only" for="{{ $formId }}-order">الترتيب</label><input id="{{ $formId }}-order" class="is-order" form="{{ $formId }}" type="number" name="sort_order" value="{{ $option->sort_order }}" min="0" max="9999" required></td>
                            <td><span class="employment-settings-state" data-tone="{{ $option->is_active ? 'active' : 'retired' }}">{{ $option->is_active ? 'فعّال' : 'متوقف' }}</span></td>
                            <td><div class="employment-settings-actions">
                                <form id="{{ $formId }}" method="post" action="{{ route('dashboard.hr.settings.options.update', [$section, $option]) }}">@csrf @method('PATCH')<button type="submit">حفظ</button></form>
                                <form method="post" action="{{ route('dashboard.hr.settings.options.toggle', [$section, $option]) }}">@csrf @method('PATCH')<button type="submit">{{ $option->is_active ? 'إيقاف' : 'تفعيل' }}</button></form>
                                @unless ($option->is_active)
                                    <form method="post" action="{{ route('dashboard.hr.settings.options.destroy', [$section, $option]) }}" data-confirm="هل تريد حذف هذا الخيار نهائياً؟">@csrf @method('DELETE')<button class="is-danger" type="submit">حذف</button></form>
                                @endunless
                            </div></td>
                        </tr>
                    @empty
                        <tr><td class="employment-settings-empty" colspan="5">لا توجد خيارات.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @elseif ($section === 'leave_types')
        <section class="employment-settings-card" aria-labelledby="settings-title">
            <header><h2 id="settings-title">أنواع الإجازات</h2><span>{{ $leaveTypes->where('is_active', true)->count() }} / {{ $leaveTypes->count() }}</span></header>
            <div class="employment-settings-table-wrap">
                <table class="employment-settings-table is-behavioural">
                    <thead><tr><th>النوع</th><th>الأجر</th><th>الرصيد السنوي</th><th>أثر الراتب</th><th>الترتيب</th><th>الحالة</th><th><span class="sr-only">الإجراءات</span></th></tr></thead>
                    <tbody>
                    @foreach ($leaveTypes as $leaveType)
                        @php($formId = 'leave-type-'.$leaveType->id)
                        <tr @class(['is-retired' => ! $leaveType->is_active])>
                            <td><label class="sr-only" for="{{ $formId }}-name">النوع</label><input id="{{ $formId }}-name" form="{{ $formId }}" name="name_ar" value="{{ $leaveType->name_ar }}" maxlength="150" required><code dir="ltr">{{ $leaveType->code }}</code></td>
                            <td><label class="employment-settings-check"><input form="{{ $formId }}" type="checkbox" name="is_paid" value="1" @checked($leaveType->is_paid)><span>مدفوعة</span></label></td>
                            <td><label class="sr-only" for="{{ $formId }}-days">الرصيد السنوي</label><input id="{{ $formId }}-days" class="is-order" form="{{ $formId }}" type="number" step="0.5" min="0" max="999.9" name="annual_days" value="{{ $leaveType->annual_days }}"></td>
                            <td><label class="sr-only" for="{{ $formId }}-effect">أثر الراتب</label><select id="{{ $formId }}-effect" form="{{ $formId }}" name="payroll_effect" required><option value="none" @selected($leaveType->payroll_effect === 'none')>بدون أثر</option><option value="unpaid_deduction" @selected($leaveType->payroll_effect === 'unpaid_deduction')>خصم إجازة غير مدفوعة</option></select></td>
                            <td><label class="sr-only" for="{{ $formId }}-order">الترتيب</label><input id="{{ $formId }}-order" class="is-order" form="{{ $formId }}" type="number" min="0" max="9999" name="sort_order" value="{{ $leaveType->sort_order }}" required></td>
                            <td><span class="employment-settings-state" data-tone="{{ $leaveType->is_active ? 'active' : 'retired' }}">{{ $leaveType->is_active ? 'فعّال' : 'متوقف' }}</span></td>
                            <td><div class="employment-settings-actions"><form id="{{ $formId }}" method="post" action="{{ route('dashboard.hr.settings.leave-types.update', $leaveType) }}">@csrf @method('PATCH')<button type="submit">حفظ</button></form><form method="post" action="{{ route('dashboard.hr.settings.leave-types.toggle', $leaveType) }}">@csrf @method('PATCH')<button type="submit">{{ $leaveType->is_active ? 'إيقاف' : 'تفعيل' }}</button></form></div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @elseif ($section === 'salary_components')
        <section class="employment-settings-card" aria-labelledby="settings-title">
            <header><h2 id="settings-title">مكوّنات الراتب</h2><span>{{ $salaryComponents->where('is_active', true)->count() }} / {{ $salaryComponents->count() }}</span></header>
            <div class="employment-settings-table-wrap">
                <table class="employment-settings-table is-behavioural">
                    <thead><tr><th>المكوّن</th><th>النوع</th><th>الاحتساب</th><th>الترتيب</th><th>الحالة</th><th><span class="sr-only">الإجراءات</span></th></tr></thead>
                    <tbody>
                    @foreach ($salaryComponents as $salaryComponent)
                        @php($formId = 'salary-component-'.$salaryComponent->id)
                        <tr @class(['is-retired' => ! $salaryComponent->is_active])>
                            <td><label class="sr-only" for="{{ $formId }}-name">المكوّن</label><input id="{{ $formId }}-name" form="{{ $formId }}" name="name_ar" value="{{ $salaryComponent->name_ar }}" maxlength="150" required><code dir="ltr">{{ $salaryComponent->code }}</code></td>
                            <td><label class="sr-only" for="{{ $formId }}-type">النوع</label><select id="{{ $formId }}-type" form="{{ $formId }}" name="component_type" required><option value="earning" @selected($salaryComponent->component_type === 'earning')>استحقاق</option><option value="deduction" @selected($salaryComponent->component_type === 'deduction')>خصم</option></select></td>
                            <td><label class="sr-only" for="{{ $formId }}-calculation">الاحتساب</label><select id="{{ $formId }}-calculation" form="{{ $formId }}" name="calculation_type" required><option value="fixed" @selected($salaryComponent->calculation_type === 'fixed')>مبلغ ثابت</option><option value="percent_of_basic" @selected($salaryComponent->calculation_type === 'percent_of_basic')>نسبة من الأساسي</option></select></td>
                            <td><label class="sr-only" for="{{ $formId }}-order">الترتيب</label><input id="{{ $formId }}-order" class="is-order" form="{{ $formId }}" type="number" min="0" max="9999" name="sort_order" value="{{ $salaryComponent->sort_order }}" required></td>
                            <td><span class="employment-settings-state" data-tone="{{ $salaryComponent->is_active ? 'active' : 'retired' }}">{{ $salaryComponent->is_active ? 'فعّال' : 'متوقف' }}</span></td>
                            <td><div class="employment-settings-actions"><form id="{{ $formId }}" method="post" action="{{ route('dashboard.hr.settings.salary-components.update', $salaryComponent) }}">@csrf @method('PATCH')<button type="submit">حفظ</button></form>@unless($salaryComponent->is_basic)<form method="post" action="{{ route('dashboard.hr.settings.salary-components.toggle', $salaryComponent) }}">@csrf @method('PATCH')<button type="submit">{{ $salaryComponent->is_active ? 'إيقاف' : 'تفعيل' }}</button></form>@endunless</div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @else
        <section class="employment-settings-card" aria-labelledby="settings-title">
            <header><h2 id="settings-title">المناوبات</h2><span>{{ $shifts->where('is_active', true)->count() }} / {{ $shifts->count() }}</span></header>
            <div class="employment-settings-table-wrap">
                <table class="employment-settings-table is-behavioural">
                    <thead><tr><th>المناوبة</th><th>البداية</th><th>النهاية</th><th>مهلة التأخير</th><th>الامتداد</th><th>الحالة</th><th><span class="sr-only">الإجراءات</span></th></tr></thead>
                    <tbody>
                    @foreach ($shifts as $shift)
                        @php($formId = 'shift-'.$shift->id)
                        <tr @class(['is-retired' => ! $shift->is_active])>
                            <td><label class="sr-only" for="{{ $formId }}-name">المناوبة</label><input id="{{ $formId }}-name" form="{{ $formId }}" name="name" value="{{ $shift->name }}" maxlength="60" required><code dir="ltr">{{ $shift->code }}</code></td>
                            <td><label class="sr-only" for="{{ $formId }}-start">البداية</label><input id="{{ $formId }}-start" form="{{ $formId }}" type="time" name="start_time" value="{{ $shift->start_time }}" required></td>
                            <td><label class="sr-only" for="{{ $formId }}-end">النهاية</label><input id="{{ $formId }}-end" form="{{ $formId }}" type="time" name="end_time" value="{{ $shift->end_time }}" required></td>
                            <td><label class="sr-only" for="{{ $formId }}-grace">مهلة التأخير</label><div class="employment-settings-unit"><input id="{{ $formId }}-grace" class="is-order" form="{{ $formId }}" type="number" min="0" max="1440" name="grace_minutes" value="{{ $shift->grace_minutes }}" required><span>دقيقة</span></div></td>
                            <td>{{ $shift->crosses_midnight ? 'تعبر منتصف الليل' : 'في اليوم نفسه' }}</td>
                            <td><span class="employment-settings-state" data-tone="{{ $shift->is_active ? 'active' : 'retired' }}">{{ $shift->is_active ? 'فعّال' : 'متوقف' }}</span></td>
                            <td><div class="employment-settings-actions"><form id="{{ $formId }}" method="post" action="{{ route('dashboard.hr.settings.shifts.update', $shift) }}">@csrf @method('PATCH')<button type="submit">حفظ</button></form><form method="post" action="{{ route('dashboard.hr.settings.shifts.toggle', $shift) }}">@csrf @method('PATCH')<button type="submit">{{ $shift->is_active ? 'إيقاف' : 'تفعيل' }}</button></form></div></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
@endsection
