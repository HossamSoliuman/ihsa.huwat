@extends('layouts.app')

@section('title', 'بوابة الخدمات والتراخيص')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'headset'])</div>
            <div>
                <h1>بوابة الخدمات والتراخيص</h1>
                <p>دورة الخدمة كاملة — من طلب الصياد إلى الرخصة الصادرة والرقابة على شروطها</p>
            </div>
        </div>
        <form method="GET" class="actions">
            <label class="field">
                <input class="input" type="search" name="q" value="{{ $query }}" placeholder="ابحث عن لوحة..." style="width:16rem">
            </label>
            <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'search']) بحث</button>
            @if ($query)
                <a href="{{ route('services.home') }}" class="btn btn-outline">إلغاء</a>
            @endif
        </form>
    </div>

    <div class="portal-hero">
        <div>
            <h2>الخدمات والتراخيص</h2>
            <p>الطلب يصل، ويُعالَج، ويُعتمد بتوقيع — ثم تُطبع الرخصة ويُتابَع الالتزام بشروطها</p>
        </div>
        <div class="tiles">
            <div class="tile"><b>{{ $dashboards }}</b><span>لوحة</span></div>
            <div class="tile"><b>{{ $tiles['open'] }}</b><span>طلب مفتوح</span></div>
            <div class="tile"><b>{{ $tiles['approval'] }}</b><span>بانتظار الاعتماد</span></div>
            <div class="tile"><b>{{ $tiles['staff'] }}</b><span>موظف نشط</span></div>
            <div class="tile"><b>{{ $tiles['licenses'] }}</b><span>رخصة سارية</span></div>
            <div class="tile"><b>{{ $tiles['tickets'] }}</b><span>تذكرة دعم</span></div>
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
            <p style="font-size:.78rem;line-height:1.9;color:hsl(var(--muted-foreground))">المعالجة والاعتماد خطوتان لا خطوة: المعالج يقترح رقم الرخصة، ولا تصدر إلا بتوقيع مسؤول مختص.</p>
            <p style="font-size:.78rem;line-height:1.9;color:hsl(var(--muted-foreground))">لا يُسند طلب إلا لموظف يملك صلاحيته وتقع خدمته وميناؤه داخل تخويله.</p>
            <p style="font-size:.78rem;line-height:1.9;color:hsl(var(--muted-foreground))">الطلب بعد الاعتماد أو الرفض مغلق — لا يُعاد فتحه، ويُقدَّم بدله طلب جديد.</p>
        </div>
    </div>
@endsection
