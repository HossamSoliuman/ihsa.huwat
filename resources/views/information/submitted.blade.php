@extends('layouts.information')

@section('title', 'تم استلام السجل')

@section('content')
<section class="info-success-page" aria-labelledby="success-title">
    <div class="info-success-card">
        <div class="info-success-visual" aria-hidden="true">
            <span class="info-success-ring"><svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"></path></svg></span>
            <i></i><i></i><i></i><i></i><i></i><i></i>
        </div>

        <p class="info-eyebrow info-eyebrow-centered"><span></span>اكتمل الإرسال<span></span></p>
        <h1 id="success-title">تم استلام البيانات بنجاح</h1>
        <p class="info-success-lead">تم حفظ البيانات في منصة حوات، ويمكن استخدام الرقم المرجعي عند التواصل مع فريق الميناء.</p>

        <div class="info-reference-card">
            <span>الرقم المرجعي</span>
            <div><strong dir="ltr" data-info-reference>{{ $receipt['reference'] }}</strong><button type="button" data-info-copy-reference aria-label="نسخ الرقم المرجعي"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 9h10v10H9V9Zm-4 6H4V5h10v1"></path></svg><span>نسخ</span></button></div>
            <small data-info-copy-status aria-live="polite">احتفظ بهذا الرقم للرجوع إلى السجل</small>
        </div>

        <dl class="info-success-summary">
            <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 14 6.5 8h11L20 14m-16 0h16l-2 5H6l-2-5Z"></path></svg>القارب</dt><dd>{{ $receipt['boat_name'] }}</dd></div>
            <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 21V9l7-5 7 5v12m-9 0v-6h4v6"></path></svg>الميناء</dt><dd>{{ $receipt['port_name'] }}</dd></div>
            <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3h10v18H7V3Zm3 4h4m-4 4h4m-4 4h3"></path></svg>حالة السجل</dt><dd><span class="info-status-badge"><i></i> تم الحفظ</span></dd></div>
        </dl>

        <div class="info-success-note">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Zm0-6v-4m0-4h.01"></path></svg>
            <p><strong>ما الخطوة التالية؟</strong><span>يمكنك الآن إضافة سجل جديد. لن تُعرض بيانات هذا السجل مرة أخرى حفاظاً على الخصوصية.</span></p>
        </div>

        <a class="info-button info-button-primary info-success-action" href="{{ route('information.create') }}"><span>إضافة سجل جديد</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg></a>
    </div>
</section>
@endsection
