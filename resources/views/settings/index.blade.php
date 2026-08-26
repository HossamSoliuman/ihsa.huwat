@extends('layouts.app')

@section('title', 'الإعدادات')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'settings'])</div>
            <div>
                <h1>الإعدادات</h1>
                <p>إعدادات النظام العامة والتكامل — وما يُحرَّر منها هنا هو تفضيلات الإشعارات</p>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif

    <div class="cards-grid" style="margin-bottom:1.25rem">
        <a href="{{ route('admin.index') }}" class="entity-card" style="flex-direction:row;align-items:center;gap:.75rem;padding:1rem">
            <div class="kpi-icon primary">@include('partials.icon', ['name' => 'shield-check'])</div>
            <div style="flex:1;min-width:0">
                <p style="font-size:.875rem;font-weight:700">مركز إدارة النظام</p>
                <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">إدارة البيانات الأساسية والإعدادات المركزية</p>
            </div>
            @include('partials.icon', ['name' => 'external-link'])
        </a>
        <a href="{{ route('admin.tab', 'permissions') }}" class="entity-card" style="flex-direction:row;align-items:center;gap:.75rem;padding:1rem">
            <div class="kpi-icon info">@include('partials.icon', ['name' => 'users'])</div>
            <div style="flex:1;min-width:0">
                <p style="font-size:.875rem;font-weight:700">إدارة الصلاحيات</p>
                <p style="font-size:.72rem;color:hsl(var(--muted-foreground))">الأدوار والنطاق الجغرافي للمناطق والمحافظات والموانئ</p>
            </div>
            @include('partials.icon', ['name' => 'external-link'])
        </a>
    </div>

    <form method="POST" action="{{ route('subadmin.settings.update') }}">
        @csrf
        @method('PUT')
        <div class="settings-grid">
            <div class="settings-panel">
                <div class="p-head">@include('partials.icon', ['name' => 'bell']) <h3>الإشعارات</h3></div>
                @forelse ($channels as $channel)
                    <div class="set-row">
                        <div>
                            <span style="font-size:.82rem">{{ $channel->label }}</span>
                            @if ($channel->description)<p style="font-size:.7rem;color:hsl(var(--muted-foreground))">{{ $channel->description }}</p>@endif
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="channels[{{ $channel->channel }}]" value="1" @checked($channel->enabled)>
                            <span></span>
                        </label>
                    </div>
                @empty
                    <p style="font-size:.78rem;color:hsl(var(--muted-foreground))">لا توجد قنوات إشعار مسجّلة.</p>
                @endforelse
            </div>

            @foreach ($panels as $panel)
                <div class="settings-panel">
                    <div class="p-head">@include('partials.icon', ['name' => $panel['icon']]) <h3>{{ $panel['title'] }}</h3></div>
                    @foreach ($panel['rows'] as $row)
                        <div class="set-row">
                            <span class="s-label">{{ $row['label'] }}</span>
                            <span class="s-value {{ $row['tone'] ?? '' }}">{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endforeach

            <div class="settings-panel">
                <div class="p-head">@include('partials.icon', ['name' => 'database']) <h3>ملاحظات الخصوصية</h3></div>
                <p style="font-size:.8rem;line-height:1.9;color:hsl(var(--muted-foreground))">{{ $privacyNote }}</p>
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top:1.25rem">
            @include('partials.icon', ['name' => 'save']) حفظ الإعدادات
        </button>
    </form>
@endsection
