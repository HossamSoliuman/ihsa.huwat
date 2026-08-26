@extends('layouts.app')

@section('title', 'المستخدمون والصلاحيات')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'user-cog'])</div>
            <div>
                <h1>المستخدمون والصلاحيات</h1>
                <p>إدارة المستخدمين والأدوار حسب المستوى الإداري (RBAC)</p>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('admin.tab', 'permissions') }}" class="btn btn-primary">
                @include('partials.icon', ['name' => 'shield-check']) إدارة الصلاحيات في مركز الإدارة
            </a>
        </div>
    </div>

    <div class="stat-grid cols-4" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي المستخدمين', 'value' => $stats['total'], 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'مستخدمون نشطون', 'value' => $stats['active'], 'icon' => 'user-check', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'إدارة عليا', 'value' => $stats['admins'], 'icon' => 'shield-check', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'الأدوار المستخدمة', 'value' => $stats['roles'], 'icon' => 'user-cog', 'tone' => 'primary'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>بحث</span><input class="input" type="search" name="q" value="{{ request('q') }}" placeholder="الاسم، البريد، المنطقة..."></label>
        <label class="field"><span>الدور</span>
            <select class="select" name="role" onchange="this.form.submit()">
                <option value="">كل الأدوار</option>
                @foreach ($roles as $value => $label)<option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>@endforeach
            </select>
        </label>
        <button type="submit" class="btn btn-primary">بحث</button>
        <a href="{{ route('subadmin.users') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الاسم</th><th>البريد الإلكتروني</th><th>الدور</th><th>مستوى الصلاحية</th><th>المنطقة</th><th>المحافظة</th><th>الميناء</th><th>الاعتماد</th><th>التصدير</th><th>الحالة</th></tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td style="font-weight:600">{{ $user->full_name ?: '—' }}</td>
                        <td style="font-family:monospace;font-size:.72rem;color:hsl(var(--muted-foreground))" dir="ltr">{{ $user->user_email }}</td>
                        <td style="font-weight:600">{{ $roles[$user->role] ?? $user->role }}</td>
                        <td><span class="pill pill-sky">{{ $user->scope_level }}</span></td>
                        <td style="color:hsl(var(--muted-foreground))">{{ $user->region ?: 'كل المناطق' }}</td>
                        <td style="color:hsl(var(--muted-foreground))">{{ $user->governorate ?: 'كل المحافظات' }}</td>
                        <td style="color:hsl(var(--muted-foreground))">{{ $user->port ?: 'كل الموانئ' }}</td>
                        <td>{{ $user->can_approve ? 'نعم' : '—' }}</td>
                        <td>{{ $user->can_export ? 'نعم' : '—' }}</td>
                        <td><span class="badge {{ $user->active ? 'badge-ok' : 'badge-warn' }}">{{ $user->active ? 'نشط' : 'موقوف' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا يوجد مستخدمون مطابقون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="note-box">
        @include('partials.icon', ['name' => 'send'])
        <div>
            <p class="n-title">إضافة المستخدمين</p>
            <p class="n-body">لا تُنشأ سجلات المستخدمين من هذه الصفحة: تُضاف عبر تبويب «الصلاحيات» في مركز إدارة النظام، فيبقى مصدر الأدوار واحدًا ومسار تعديلها مسجّلًا في سجل العمليات.</p>
        </div>
    </div>
@endsection
