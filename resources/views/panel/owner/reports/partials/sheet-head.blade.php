{{-- ترويسة تقارير المالك المطبوعة: المالك يمينًا، والتقرير وفترته يسارًا، ثم العنوان والفلاتر. --}}
<header class="head">
    <div>
        <h1>{{ $owner->name }}</h1>
        <p>هاتف: <span class="num" dir="ltr">{{ $owner->phone ?? '—' }}</span></p>
        <p>{{ config('hawat.name') }}</p>
    </div>
    <div style="text-align:left">
        <p>التقرير: <b>{{ $meta['title'] }}</b></p>
        <p>الفترة: <b>{{ $period }}</b></p>
        <p>تاريخ الإصدار: <span class="num">{{ now()->format('Y-m-d') }}</span></p>
    </div>
</header>

<div class="title">{{ $title ?? $meta['title'] }}</div>

@if (! empty($chips))
    <div class="chips">
        @foreach ($chips as $label => $value)<span>{{ $label }}: <b>{{ $value }}</b></span>@endforeach
    </div>
@endif
