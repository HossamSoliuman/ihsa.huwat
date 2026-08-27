@extends('layouts.app')

@section('title', 'بوابة الإحصاء')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'database'])</div>
            <div>
                <h1>بوابة الإحصاء الموحدة</h1>
                <p>نقطة الدخول المركزية لكل لوحات ومخرجات التحليل والإحصاء في النظام</p>
            </div>
        </div>
        <form method="GET" class="actions">
            <label class="field">
                <input class="input" type="search" name="q" value="{{ $query }}" placeholder="ابحث عن لوحة..." style="width:16rem">
            </label>
            <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'search']) بحث</button>
            @if ($query)
                <a href="{{ route('stats.home') }}" class="btn btn-outline">إلغاء</a>
            @endif
        </form>
    </div>

    <div class="portal-hero">
        <div>
            <h2>الإحصاء</h2>
            <p>من البحر إلى السوق — تتبّع واعتماد وتحليل وتقرير</p>
        </div>
        <div class="tiles">
            <div class="tile"><b>{{ $dashboards }}</b><span>لوحة</span></div>
            <div class="tile"><b>{{ $groupCount }}</b><span>مجموعات</span></div>
            <div class="tile"><b>15</b><span>مؤشر</span></div>
            <div class="tile"><b>6</b><span>مصادر</span></div>
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
            @include('partials.icon', ['name' => 'shield-check']) ملاحظة الحوكمة والأمان
        </p>
        <div class="gov-grid" style="grid-template-columns:1fr">
            <p style="font-size:.78rem;line-height:1.9;color:hsl(var(--muted-foreground))">كل المؤشرات تمرّ ببوابة الحوكمة الثلاثية قبل العرض — اختبار، اعتماد تقني، اعتماد رسمي.</p>
            <p style="font-size:.78rem;line-height:1.9;color:hsl(var(--muted-foreground))">تُطبَّق الصلاحيات الجغرافية على كل لوحة حسب نطاق المستخدم.</p>
            <p style="font-size:.78rem;line-height:1.9;color:hsl(var(--muted-foreground))">مصادر البيانات: سجلات المصيد، الرحلات، المزادات، المناطق، الأنواع.</p>
        </div>
    </div>
@endsection
