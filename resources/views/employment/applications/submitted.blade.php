@extends('layouts.employment')

@section('title', 'تم إرسال طلبك')
@section('body-class', 'employment-submitted-page employment-hud-public')

@section('content')
<section class="employment-state-page employment-success-page"><div class="employment-container"><article class="employment-success-card">
    <div class="employment-success-mark" aria-hidden="true"><svg viewBox="0 0 96 96"><circle cx="48" cy="48" r="39"></circle><path d="m30 49 12 12 25-29"></path></svg></div>
    <span class="employment-eyebrow">وصل طلبك بنجاح</span><h1>{{ $receipt ? 'شكراً، تم استلام طلبك' : 'تم تسجيل رقم الطلب' }}</h1>
    <p>@if($receipt) استلمنا طلبك للتقديم على وظيفة <strong>{{ $receipt['job_title'] }}</strong>. سيقوم فريق التوظيف بمراجعته والتواصل معك إذا تم ترشيحك. @else احتفظ بالرقم المرجعي أدناه لمتابعة مراسلات الطلب. @endif</p>
    <div class="employment-reference-ticket"><small>الرقم المرجعي لطلبك</small><strong class="mono" data-application-reference>{{ $reference }}</strong><button type="button" data-copy-reference>نسخ الرقم</button></div>
    <p class="employment-copy-status" data-copy-status aria-live="polite"></p>
    @if($receipt)<dl class="employment-receipt-details"><div><dt>الوظيفة</dt><dd>{{ $receipt['job_title'] }}</dd></div><div><dt>مرجع الوظيفة</dt><dd>{{ $receipt['job_reference'] }}</dd></div><div><dt>البريد</dt><dd dir="ltr">{{ $receipt['email'] }}</dd></div></dl>@endif
    <div class="employment-next-steps"><h2>ماذا يحدث بعد ذلك؟</h2><ol><li><span>1</span><div><strong>مراجعة الطلب</strong><p>يتحقق فريق التوظيف من البيانات والمرفقات.</p></div></li><li><span>2</span><div><strong>التواصل</strong><p>نتواصل مع المرشحين عبر الجوال أو البريد.</p></div></li><li><span>3</span><div><strong>القرار النهائي</strong><p>يُستكمل التعيين وإنشاء حساب الموظف بعد القبول.</p></div></li></ol></div>
    <div class="employment-success-actions"><a class="employment-button employment-button-primary" href="{{ route('jobs.index') }}">العودة إلى الوظائف</a></div>
</article></div></section>
@endsection
