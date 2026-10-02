@extends('layouts.auth')

@section('title_prefix', 'حوات')
@section('title', 'طلب التوظيف')

@section('card')
    <div class="auth-brand">
        <div class="ico">@include('partials.icon', ['name' => 'clipboard-check'])</div>
        <div>
            <h1>طلب التوظيف — {{ $application->name }}</h1>
            <p style="font-size:.74rem;color:hsl(var(--muted-foreground))">عدّاد في {{ $application->port?->name }} لدى {{ $application->company?->name }}</p>
        </div>
    </div>

    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="auth-error">{{ $errors->first() }}</div>@endif

    <dl style="display:grid;grid-template-columns:auto 1fr;gap:.4rem .9rem;font-size:.8rem;margin:0">
        <dt style="color:hsl(var(--muted-foreground))">الحالة</dt>
        <dd style="margin:0"><span class="badge {{ $application->status_tone }}">{{ $application->status_label }}</span></dd>
        <dt style="color:hsl(var(--muted-foreground))">الجوال</dt>
        <dd style="margin:0" class="num"><bdi dir="ltr">{{ $application->phone }}</bdi></dd>
        <dt style="color:hsl(var(--muted-foreground))">الجولة</dt>
        <dd style="margin:0">{{ $application->round?->title }}</dd>
        <dt style="color:hsl(var(--muted-foreground))">تاريخ التقديم</dt>
        <dd style="margin:0" class="num">{{ $application->created_at->format('Y-m-d H:i') }}</dd>
        @if ($application->rejection_reason)
            <dt style="color:hsl(var(--muted-foreground))">سبب الرفض</dt>
            <dd style="margin:0">{{ $application->rejection_reason }}</dd>
        @endif
    </dl>

    @if ($application->isPending() && ! $application->isVerified())
        <form class="auth-form" method="POST" action="{{ route('counter-apply.verify', $application->token) }}">
            @csrf
            <label class="field">
                <span>رمز التحقق المرسل إلى جوالك</span>
                <input class="input num" name="code" dir="ltr" inputmode="numeric" maxlength="6" autocomplete="one-time-code" required autofocus>
            </label>
            <button class="btn btn-primary" type="submit">@include('partials.icon', ['name' => 'check-circle']) توثيق الجوال</button>
        </form>
        <form method="POST" action="{{ route('counter-apply.resend', $application->token) }}">
            @csrf
            <button class="btn btn-outline" type="submit" style="width:100%;justify-content:center">إعادة إرسال الرمز</button>
        </form>
    @elseif ($application->status === \App\Models\CounterApplication::APPROVED)
        <p class="card-sub" style="line-height:1.9">قُبلت عدّادًا. ادخل تطبيق حوات أو بوابته بجوالك وكلمة المرور التي اخترتها.</p>
        <a href="{{ route('panel.login') }}" class="btn btn-primary" style="justify-content:center">@include('partials.icon', ['name' => 'log-in']) الدخول</a>
    @elseif ($application->isPending())
        <p class="card-sub" style="line-height:1.9">طلبك عند الشركة. يصلك قرارها برسالة نصية، واحفظ رابط هذه الصفحة لمتابعته.</p>
    @endif

    @if ($application->isPending())
        <form method="POST" action="{{ route('counter-apply.withdraw', $application->token) }}" onsubmit="return confirm('سحب طلبك؟')">
            @csrf
            <button class="btn btn-outline" type="submit" style="width:100%;justify-content:center">@include('partials.icon', ['name' => 'x-circle']) سحب الطلب</button>
        </form>
    @endif
@endsection
