@extends('layouts.app')

@section('title', 'طلبات التوظيف')

@php
    use App\Models\CounterApplication;

    $tabs = [
        CounterApplication::PENDING => ['label' => 'بانتظار المراجعة', 'icon' => 'inbox'],
        CounterApplication::APPROVED => ['label' => 'مقبولة', 'icon' => 'check-circle'],
        CounterApplication::REJECTED => ['label' => 'مرفوضة', 'icon' => 'x-circle'],
        CounterApplication::WITHDRAWN => ['label' => 'مسحوبة', 'icon' => 'archive'],
        'all' => ['label' => 'الكل', 'icon' => 'list-checks'],
    ];
@endphp

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'user-plus'])</div>
            <div>
                <h1>طلبات التوظيف</h1>
                <p>من تقدّم لوظيفة عدّاد في جولاتك ووثّق جواله — الاعتماد ينشئ حسابه في ميناء الجولة</p>
            </div>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash-error">{{ $errors->first() }}</div>@endif

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <input type="hidden" name="status" value="{{ $status }}">
        <label class="field"><span>بحث</span><input class="input" name="search" value="{{ request('search') }}" placeholder="الاسم أو الجوال أو الهوية..."></label>
        <label class="field"><span>الجولة</span>
            <select class="select" name="round" onchange="this.form.submit()">
                <option value="">كل الجولات</option>
                @foreach ($rounds as $r)<option value="{{ $r->id }}" @selected(request('round') === (string) $r->id)>{{ $r->title }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الميناء</span>
            <select class="select" name="port" onchange="this.form.submit()">
                <option value="">كل الموانئ</option>
                @foreach ($ports as $p)<option value="{{ $p->id }}" @selected(request('port') === (string) $p->id)>{{ $p->name }}</option>@endforeach
            </select>
        </label>
        <button class="btn btn-primary">تصفية</button>
        <a href="{{ route('panel.company.applications', ['status' => $status]) }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <nav class="seg" style="margin-bottom:1.25rem" aria-label="حالة الطلب">
        @foreach ($tabs as $key => $tab)
            <a href="{{ route('panel.company.applications', ['status' => $key] + request()->only('round', 'port', 'search')) }}" @class(['is-active' => $status === $key])>
                @include('partials.icon', ['name' => $tab['icon']]) {{ $tab['label'] }}
                <span class="num">({{ number_format($counts[$key]) }})</span>
            </a>
        @endforeach
    </nav>

    <div class="grid-2">
        @forelse ($applications as $item)
            <div class="card" style="display:flex;flex-direction:column;gap:.9rem">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.75rem">
                    <div>
                        <div style="font-weight:700;font-size:1rem">{{ $item->name }}</div>
                        <div class="card-sub">{{ $item->port?->name }} · {{ $item->round?->title }}</div>
                    </div>
                    <span class="badge {{ $item->status_tone }}">{{ $item->status_label }}</span>
                </div>

                <dl style="display:grid;grid-template-columns:auto 1fr;gap:.35rem .9rem;font-size:.8rem;margin:0">
                    <dt style="color:hsl(var(--muted-foreground))">الجوال</dt>
                    <dd style="margin:0" dir="ltr" class="num"><a href="tel:{{ $item->phone }}">{{ $item->phone }}</a></dd>
                    <dt style="color:hsl(var(--muted-foreground))">الهوية</dt>
                    <dd style="margin:0" class="num"><bdi dir="ltr">{{ $item->national_id }}</bdi></dd>
                    <dt style="color:hsl(var(--muted-foreground))">تاريخ الميلاد</dt>
                    <dd style="margin:0" class="num"><bdi dir="ltr">{{ $item->birth_date?->format('Y-m-d') ?? '—' }}</bdi></dd>
                    <dt style="color:hsl(var(--muted-foreground))">المؤهل</dt>
                    <dd style="margin:0">{{ $item->qualification ?? '—' }}</dd>
                    <dt style="color:hsl(var(--muted-foreground))">سنوات الخبرة</dt>
                    <dd style="margin:0" class="num">{{ $item->experience_years ?? '—' }}</dd>
                    <dt style="color:hsl(var(--muted-foreground))">البريد</dt>
                    <dd style="margin:0" dir="ltr">{{ $item->email ?? '—' }}</dd>
                    <dt style="color:hsl(var(--muted-foreground))">تاريخ الطلب</dt>
                    <dd style="margin:0" class="num"><bdi dir="ltr">{{ $item->created_at->format('Y-m-d H:i') }}</bdi> <span style="color:hsl(var(--muted-foreground))">({{ $item->created_at->diffForHumans() }})</span></dd>
                </dl>

                @if ($item->notes)
                    <p style="margin:0;font-size:.8rem;line-height:1.7;padding:.6rem .8rem;border:1px solid var(--hair);background:hsl(var(--muted) / .4);white-space:pre-line">{{ $item->notes }}</p>
                @endif

                @if ($item->isPending())
                    <div style="display:flex;flex-wrap:wrap;gap:.5rem;justify-content:flex-end;align-items:flex-start;padding-top:.25rem;border-top:1px solid var(--hair)">
                        <details style="flex:1 1 220px">
                            <summary class="btn btn-outline" style="list-style:none;cursor:pointer">@include('partials.icon', ['name' => 'x-circle']) رفض</summary>
                            <form method="POST" action="{{ route('panel.company.applications.reject', $item->id) }}" style="display:grid;gap:.5rem;margin-top:.6rem">
                                @csrf
                                <label class="field"><span>سبب الرفض (يصله برسالة)</span><textarea class="input" name="rejection_reason" rows="2" maxlength="1000"></textarea></label>
                                <button type="submit" class="btn btn-outline" style="justify-self:start">تأكيد الرفض</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('panel.company.applications.approve', $item->id) }}" onsubmit="return confirm('اعتماد «{{ $item->name }}» عدّادًا في {{ $item->port?->name }} وإنشاء حسابه؟')">
                            @csrf
                            <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'check-circle']) اعتماد وإنشاء الحساب</button>
                        </form>
                    </div>
                @elseif ($item->reviewed_at)
                    <div class="card-sub" style="padding-top:.6rem;border-top:1px solid var(--hair)">
                        {{ $item->status_label }} بواسطة {{ $item->reviewer?->name ?? '—' }} · <bdi dir="ltr" class="num">{{ $item->reviewed_at->format('Y-m-d H:i') }}</bdi>
                        @if ($item->rejection_reason)
                            <div style="margin-top:.35rem">السبب: {{ $item->rejection_reason }}</div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="card" style="grid-column:1/-1"><p class="card-sub" style="padding:1.5rem 0;text-align:center">لا طلبات {{ $status === CounterApplication::PENDING ? 'بانتظار المراجعة' : 'مطابقة' }}</p></div>
        @endforelse
    </div>
    @include('partials.pagination', ['paginator' => $applications])
@endsection
