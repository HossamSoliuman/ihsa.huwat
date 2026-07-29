@extends('layouts.dashboard')

@section('title', 'التنبيهات والرقابة')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/alerts.css') }}">
@endpush

@section('content')
<div class="alerts-console">
    <header class="alerts-command">
        <div>
            <span class="alerts-kicker"><i></i> LIVE CONTROL FEED</span>
            <h1>التنبيهات والرقابة</h1>
            <p>قراءة فورية للمخاطر التشغيلية وجودة التوثيق والتغطية البشرية ضمن نطاق صلاحيتك.</p>
        </div>
        <dl class="alerts-scope">
            <div><dt>الموانئ المراقبة</dt><dd>{{ $monitoredPortsCount }}</dd></div>
            <div><dt>آخر تحديث</dt><dd dir="ltr">{{ now()->format('H:i:s') }}</dd></div>
        </dl>
    </header>

    <section class="alerts-vitals" aria-label="ملخص التنبيهات">
        <article class="is-critical"><span>تنبيهات حرجة</span><strong>{{ $criticalCount }}</strong><small>تتطلب تدخلاً مباشرًا</small></article>
        <article class="is-warning"><span>تنبيهات تحذيرية</span><strong>{{ $warningCount }}</strong><small>تحتاج إلى المتابعة</small></article>
        <article><span>قوارب لم يبدأ إحصاؤها</span><strong>{{ $countByType->get('قارب وصل ولم يبدأ إحصاؤه', 0) }}</strong><small>بعد مهلة الإسناد</small></article>
        <article><span>فروقات كبيرة معلّقة</span><strong>{{ $countByType->get('فرق تجاوز الحد المسموح', 0) }}</strong><small>بانتظار الاعتماد</small></article>
        <article><span>موانئ غير مغطاة</span><strong>{{ $countByType->get('ميناء غير مغطى', 0) }}</strong><small>بدون تكليف اليوم</small></article>
        <article><span>موانئ مزدحمة</span><strong>{{ $countByType->get('ازدحام قوارب في ميناء', 0) }}</strong><small>ثلاثة قوارب أو أكثر</small></article>
    </section>

    <section class="alerts-feed">
        <header>
            <div><span>ACTIVE SIGNALS</span><h2>كل التنبيهات النشطة</h2></div>
            <b>{{ $alerts->count() }} تنبيه</b>
        </header>

        @if($alerts->isEmpty())
            <div class="alerts-clear"><i>✓</i><h3>الوضع التشغيلي مستقر</h3><p>لا توجد تنبيهات نشطة حاليًا ضمن نطاق صلاحيتك.</p></div>
        @else
            <div class="alerts-table-wrap">
                <table class="alerts-table">
                    <thead><tr><th>الحالة</th><th>النوع</th><th>تفاصيل الإشارة</th><th>الوقت</th></tr></thead>
                    <tbody>
                    @foreach($alerts as $alert)
                        <tr class="alert-row-{{ $alert['severity'] }}">
                            <td><span class="alert-severity"><i></i>{{ $alert['severity'] === 'critical' ? 'حرج' : ($alert['severity'] === 'warning' ? 'تحذير' : 'معلومة') }}</span></td>
                            <td><strong>{{ $alert['type'] }}</strong></td>
                            <td>{{ $alert['message'] }}</td>
                            <td><time dir="ltr" datetime="{{ $alert['time']->toIso8601String() }}">{{ $alert['time']->format('Y/m/d H:i') }}</time></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
