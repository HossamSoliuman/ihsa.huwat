@extends('layouts.print')

@section('title', 'رخصة '.$request->fisher_name)

@push('print-styles')
<style>
    @page { size: A4; margin: 18mm; }
    .card-shell { max-width: 150mm; margin: 0 auto; border: 2px solid #0369a1; border-radius: 12px; overflow: hidden; }
    .card-top { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: #0369a1; color: #fff; padding: 12px 16px; }
    .card-top .t { font-size: 15px; font-weight: 800; }
    .card-top .s { margin-top: 2px; font-size: 10px; color: #e0f2fe; }
    .card-top .n { font-family: monospace; font-size: 13px; font-weight: 800; letter-spacing: .04em; }
    .card-body { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0; }
    .row { border-bottom: 1px solid #e2e8f0; border-left: 1px solid #e2e8f0; padding: 8px 14px; }
    .row:nth-child(even) { border-left: 0; }
    .row .l { font-size: 9.5px; color: #64748b; }
    .row .v { margin-top: 2px; font-size: 12px; font-weight: 700; }
    .row.wide { grid-column: span 2; border-left: 0; }
    .sign { display: flex; justify-content: space-between; gap: 16px; padding: 14px 16px; background: #f8fafc; }
    .sign .l { font-size: 9.5px; color: #64748b; }
    .sign .v { margin-top: 3px; font-size: 12px; font-weight: 800; color: #0c4a6e; }
    .stamp { margin-top: 14px; text-align: center; font-size: 10px; color: #64748b; }
</style>
@endpush

@section('content')
    <div class="doc-head">
        <h1>{{ config('hawat.ministry') }}</h1>
        <p class="sub">{{ config('hawat.sector') }} — {{ config('hawat.name') }}</p>
        <p class="meta">بطاقة رخصة صادرة عن قسم الخدمات والتراخيص · طُبعت في {{ now()->toDateString() }}</p>
    </div>

    <div class="card-shell">
        <div class="card-top">
            <div>
                <p class="t">{{ $request->serviceType->name }}</p>
                <p class="s">طلب رقم {{ $request->request_number }} · تاريخ التقديم {{ $request->submitted_date?->toDateString() ?? '—' }}</p>
            </div>
            <p class="n">{{ $request->new_license_number ?: $request->license_number ?: '—' }}</p>
        </div>

        <div class="card-body">
            <div class="row"><p class="l">اسم المرخّص له</p><p class="v">{{ $request->fisher_name }}</p></div>
            <div class="row"><p class="l">رقم الهوية</p><p class="v">{{ $request->national_id ?: '—' }}</p></div>
            <div class="row"><p class="l">الجنسية</p><p class="v">{{ $request->nationality ?: $request->nationality_type }}</p></div>
            <div class="row"><p class="l">تاريخ الميلاد</p><p class="v">{{ $request->birth_date?->toDateString() ?? '—' }}</p></div>
            <div class="row"><p class="l">المهنة</p><p class="v">{{ $request->profession ?: 'صياد سمك' }}</p></div>
            <div class="row"><p class="l">فصيلة الدم</p><p class="v">{{ $request->blood_type ?: '—' }}</p></div>
            <div class="row"><p class="l">صاحب العمل</p><p class="v">{{ $request->employer ?: '—' }}</p></div>
            <div class="row"><p class="l">رقم الجوال</p><p class="v">{{ $request->phone ?: '—' }}</p></div>
            <div class="row"><p class="l">الميناء / المرسى</p><p class="v">{{ $request->port?->name ?: '—' }}</p></div>
            <div class="row"><p class="l">المحافظة</p><p class="v">{{ $request->port?->governorate?->name ?: '—' }}</p></div>
            <div class="row"><p class="l">المنطقة</p><p class="v">{{ $request->regionName() ?: '—' }}</p></div>
            <div class="row"><p class="l">المركز</p><p class="v">{{ $request->center ?: '—' }}</p></div>
            <div class="row"><p class="l">القارب المتصل</p><p class="v">{{ $request->boat?->name ?: '—' }}</p></div>
            <div class="row"><p class="l">موسم الصيد</p><p class="v">{{ $request->fishingSeason?->name ?: '—' }}</p></div>
            <div class="row"><p class="l">تاريخ الإصدار</p><p class="v">{{ ($request->processed_date ?? $request->submitted_date)?->toDateString() ?? '—' }}</p></div>
            <div class="row"><p class="l">تاريخ الانتهاء</p><p class="v">{{ $request->new_license_expiry?->toDateString() ?? '—' }}</p></div>
        </div>

        <div class="sign">
            <div>
                <p class="l">اعتمدها المسؤول المختص</p>
                <p class="v">{{ $request->approved_by ?: '—' }}</p>
            </div>
            <div style="text-align:left">
                <p class="l">وقت الاعتماد</p>
                <p class="v">{{ $request->approved_at?->format('Y-m-d H:i') ?? '—' }}</p>
            </div>
        </div>
    </div>

    <p class="stamp">هذه البطاقة صادرة إلكترونيًا من منصة {{ config('hawat.name') }}، ويُتحقّق منها برقم الطلب {{ $request->request_number }}.</p>

    <div class="doc-foot">{{ config('hawat.ministry') }} — {{ config('hawat.sector') }}</div>
@endsection
