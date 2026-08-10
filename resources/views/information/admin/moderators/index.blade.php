@extends('layouts.information-admin')

@section('title', 'المشرفون')

@section('content')
@use('App\Models\UserScope')

<header class="info-admin-header">
    <div>
        <p class="info-eyebrow"><span></span>لوحة مركز المعلومات<span></span></p>
        <h1>المشرفون</h1>
    </div>

    <a class="info-button info-button-primary" href="{{ route('information.admin.moderators.create') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
        <span>مشرف جديد</span>
    </a>
</header>

<section class="info-form-card info-admin-panel" aria-labelledby="moderators-title">
    <div class="info-admin-panel-head">
        <h2 id="moderators-title">حسابات المشرفين</h2>

        <form class="info-admin-filters" method="get" action="{{ route('information.admin.moderators.index') }}">
            <div class="info-field">
                <label class="sr-only" for="filter-q">بحث</label>
                <input id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="ابحث بالاسم أو اسم المستخدم أو الجوال أو الهوية">
            </div>

            <div class="info-field">
                <label class="sr-only" for="filter-scope-type">مستوى النطاق</label>
                <select id="filter-scope-type" name="scope_type">
                    <option value="">كل المستويات</option>
                    @foreach (UserScope::TYPES as $type => $label)
                        <option value="{{ $type }}" @selected(($filters['scope_type'] ?? null) === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="info-field">
                <label class="sr-only" for="filter-status">الحالة</label>
                <select id="filter-status" name="status">
                    <option value="">كل الحالات</option>
                    <option value="active" @selected(($filters['status'] ?? null) === 'active')>نشط</option>
                    <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>موقوف</option>
                </select>
            </div>

            <button class="info-button info-button-primary" type="submit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Zm5.5-1.5L21 21"></path></svg>
                <span>تصفية</span>
            </button>
        </form>
    </div>

    <div class="info-admin-table-scroll">
        <table class="info-admin-table">
            <caption class="sr-only">حسابات مشرفي مركز المعلومات</caption>
            <thead>
                <tr>
                    <th scope="col">المشرف</th>
                    <th scope="col">اسم المستخدم</th>
                    <th scope="col">الجوال</th>
                    <th scope="col">الهوية</th>
                    <th scope="col">المستوى</th>
                    <th scope="col">النطاق</th>
                    <th scope="col">آخر دخول</th>
                    <th scope="col">الحالة</th>
                    <th scope="col"><span class="sr-only">الإجراءات</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($moderators as $moderator)
                    @php
                        $assigned = $moderator->assignedScopes;
                        $scopeType = $assigned->first()?->scope_type;
                        $names = $assigned
                            ->map(fn ($assignment) => $scopeLabels[$assignment->scope_type][$assignment->scope_id] ?? null)
                            ->filter()
                            ->values();
                    @endphp

                    <tr @class(['is-retired' => ! $moderator->is_active])>
                        <td><strong>{{ $moderator->full_name }}</strong></td>
                        <td dir="ltr">{{ $moderator->username }}</td>
                        <td dir="ltr">{{ $moderator->phone ?? '—' }}</td>
                        <td dir="ltr">{{ $moderator->national_id ?? '—' }}</td>
                        <td>{{ $scopeType === null ? '—' : UserScope::TYPES[$scopeType] }}</td>
                        <td>
                            {{ $names->take(3)->implode('، ') ?: '—' }}
                            @if ($names->count() > 3)
                                <small>و{{ $names->count() - 3 }} غيرها</small>
                            @endif
                        </td>
                        <td>{{ $moderator->last_login_at?->diffForHumans() ?? '—' }}</td>
                        <td>
                            <span class="info-status-chip" data-tone="{{ $moderator->is_active ? 'sea' : 'gold' }}">
                                <i aria-hidden="true"></i>{{ $moderator->is_active ? 'نشط' : 'موقوف' }}
                            </span>
                        </td>
                        <td>
                            <a class="info-admin-row-action" href="{{ route('information.admin.moderators.show', $moderator) }}"
                               aria-label="إدارة حساب {{ $moderator->full_name }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                <span>إدارة</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="info-admin-empty">لا توجد حسابات مشرفين مطابقة لعوامل التصفية الحالية.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($moderators->hasPages())
        <div class="info-admin-pagination">{{ $moderators->links() }}</div>
    @endif
</section>
@endsection
