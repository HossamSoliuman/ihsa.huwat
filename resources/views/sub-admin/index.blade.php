@extends('layouts.app')

@section('title', 'بوابة الإدارة الفرعية')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'network'])</div>
            <div>
                <h1>بوابة الإدارة الفرعية</h1>
                <p>نقطة الدخول لإدارة القطاع نفسه — الصلاحيات والهيكل والمهام والتدقيق</p>
            </div>
        </div>
        <form method="GET" class="actions">
            <label class="field">
                <input class="input" type="search" name="q" value="{{ $query }}" placeholder="ابحث عن لوحة..." style="width:16rem">
            </label>
            <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'search']) بحث</button>
            @if ($query)
                <a href="{{ route('subadmin.home') }}" class="btn btn-outline">إلغاء</a>
            @endif
        </form>
    </div>

    <div class="portal-hero">
        <div>
            <h2>قسم الإدارة الفرعية</h2>
            <p>من يفعل ماذا — ومتى، وبأي صلاحية، وبأي أثر مسجّل</p>
        </div>
        <div class="tiles">
            <div class="tile"><b>{{ $dashboards }}</b><span>لوحة</span></div>
            <div class="tile"><b>{{ $tiles['positions'] }}</b><span>منصب</span></div>
            <div class="tile"><b>{{ $tiles['openTasks'] }}</b><span>مهمة مفتوحة</span></div>
            <div class="tile"><b>{{ $tiles['unread'] }}</b><span>تنبيه جديد</span></div>
            <div class="tile"><b>{{ $tiles['alerts'] }}</b><span>إنذار قائم</span></div>
        </div>
    </div>

    @forelse ($groups as $group)
        <section class="portal-group">
            <div class="head">
                <div class="badge-icon tone-{{ $group['tone'] }}">@include('partials.icon', ['name' => $group['icon']])</div>
                <div>
                    <h3>{{ $group['title'] }}</h3>
                    <p>{{ count($group['items']) }} لوحات</p>
                </div>
            </div>
            <div class="portal-grid">
                @foreach ($group['items'] as $item)
                    <a href="{{ route($item['route']) }}" class="portal-card">
                        <div class="top">
                            <div class="p-icon">@include('partials.icon', ['name' => $item['icon']])</div>
                            <span class="go">@include('partials.icon', ['name' => 'arrow-left'])</span>
                        </div>
                        <div>
                            <p class="p-title">{{ $item['label'] }}</p>
                            <p class="p-desc">{{ $item['desc'] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="pending-card">
            @include('partials.icon', ['name' => 'search'])
            <h3>لا توجد لوحات مطابقة لبحثك</h3>
            <p>جرّب كلمة أخرى، أو ألغِ البحث لعرض القسم كاملًا.</p>
        </div>
    @endforelse

    <div class="card">
        <p class="card-title" style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem">
            @include('partials.icon', ['name' => 'shield-check']) حدود القسم
        </p>
        <div class="gov-grid" style="grid-template-columns:1fr">
            <p style="font-size:.78rem;line-height:1.9;color:hsl(var(--muted-foreground))">تحرير البيانات الأساسية يجري في مركز الإدارة وحده — هذه اللوحات تقرأ وتوجّه وتتابع.</p>
            <p style="font-size:.78rem;line-height:1.9;color:hsl(var(--muted-foreground))">كل مهمة إدارية مرتبطة بمنصب في الهيكل التنظيمي، ولا تُسند إلا لموظف يملك الصلاحية المطلوبة.</p>
            <p style="font-size:.78rem;line-height:1.9;color:hsl(var(--muted-foreground))">سجل العمليات لا يُحرَّر ولا يُحذف — قيمته في أنه لا يتغيّر بعد الكتابة.</p>
        </div>
    </div>
@endsection
