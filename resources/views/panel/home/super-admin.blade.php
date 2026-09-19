@extends('layouts.app')

@section('title', 'لوحة الإدارة')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'layers'])</div>
            <div>
                <h1>لوحة الإدارة</h1>
                <p>حسابات تطبيق حوات بأدوارها، وحال الأسطول والرحلات، ومركز المعلومات التشغيلي</p>
            </div>
        </div>
        <div class="actions">
            <a href="{{ route('panel.users') }}" class="btn btn-primary">@include('partials.icon', ['name' => 'user-plus']) حسابات التطبيق</a>
        </div>
    </div>

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'حسابات التطبيق', 'value' => number_format($stats['accounts']), 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'حسابات مفعّلة', 'value' => number_format($stats['active']), 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'القوارب', 'value' => number_format($stats['boats']), 'icon' => 'ship', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'رحلات في البحر', 'value' => number_format($stats['at_sea']), 'icon' => 'waves', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'بانتظار العدّ', 'value' => number_format($stats['awaiting_count']), 'icon' => 'clipboard', 'tone' => 'warning'])
    </div>

    <div class="grid-2">
        <div class="card">
            @include('partials.section-head', ['icon' => 'user-cog', 'title' => 'الحسابات حسب الدور', 'note' => 'كل دور وعدد حساباته'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الدور</th><th>الوصف</th><th>بوابة ويب</th><th style="text-align:center">الحسابات</th></tr></thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td style="font-weight:600">{{ $role->name }} <span style="font-size:10.5px;color:hsl(var(--muted-foreground))" dir="ltr">{{ $role->name_en }}</span></td>
                                <td style="font-size:.74rem;color:hsl(var(--muted-foreground))">{{ $role->description }}</td>
                                <td>@if ($role->has_portal)<span class="badge badge-ok">/admin</span>@else<span class="badge badge-info">التطبيق</span>@endif</td>
                                <td style="text-align:center"><a href="{{ route('panel.users', ['role' => $role->key]) }}" style="font-weight:700">{{ number_format($role->users_count) }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            @include('partials.section-head', ['icon' => 'user-plus', 'title' => 'أحدث الحسابات', 'note' => 'آخر ما أُنشئ'])
            <div class="table-card" style="border:0">
                <table class="data-table">
                    <thead><tr><th>الاسم</th><th>الجوال</th><th>الدور</th><th>الحالة</th></tr></thead>
                    <tbody>
                        @forelse ($recent as $account)
                            <tr>
                                <td style="font-weight:600">{{ $account->name }}</td>
                                <td dir="ltr" style="font-family:monospace">{{ $account->phone ?? '—' }}</td>
                                <td>{{ $account->appRole?->name }}</td>
                                <td><span class="badge {{ $account->active ? 'badge-ok' : 'badge-danger' }}">{{ $account->active ? 'مفعّل' : 'معطّل' }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا حسابات بعد</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
