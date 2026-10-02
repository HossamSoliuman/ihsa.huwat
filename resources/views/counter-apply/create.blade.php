@extends('layouts.auth')

@section('title_prefix', 'حوات')
@section('title', 'التقديم لوظيفة عدّاد')

@push('head')
    <style>
        .auth-card { width: min(44rem, 100%); }
        .auth-card .form-grid { display: grid; gap: .85rem; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .auth-card .form-grid .wide { grid-column: 1 / -1; }
        @media (max-width: 640px) { .auth-card .form-grid { grid-template-columns: 1fr; } }
        .apply-round { border: 1px solid var(--hair); padding: .65rem .8rem; font-size: .78rem; line-height: 1.8; }
    </style>
@endpush

@section('card')
    <div class="auth-brand">
        <div class="ico">@include('partials.icon', ['name' => 'clipboard-check'])</div>
        <div>
            <h1>اعمل عدّادًا في الميناء</h1>
            <p style="font-size:.74rem;color:hsl(var(--muted-foreground))">تستلم المصيد العائد وتعدّه صنفًا صنفًا لدى شركة التشغيل — قدّم في جولة مفتوحة</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="auth-error">{{ $errors->first() }}</div>
    @endif

    @if ($rounds->isEmpty())
        <p class="card-sub" style="line-height:1.9">لا توظيف مفتوح الآن في أي ميناء. تُعلن شركات التشغيل جولاتها هنا — عُد لاحقًا.</p>
        <a href="{{ route('landing') }}" class="btn btn-outline">العودة إلى حوات</a>
    @else
        <form class="auth-form" method="POST" action="{{ route('counter-apply.store') }}" id="apply-form" autocomplete="off">
            @csrf
            <input type="hidden" name="g-recaptcha-response" value="">
            <div class="form-grid">
                <label class="field"><span>المنطقة *</span><select class="select" id="ap-region" required></select></label>
                <label class="field"><span>المدينة / المحافظة *</span><select class="select" id="ap-governorate" required></select></label>
                <label class="field wide"><span>الميناء *</span><select class="select" name="hiring_round_id" id="ap-round" required></select></label>
                <div class="apply-round wide" id="ap-round-info" hidden></div>

                <label class="field wide"><span>الاسم الكامل *</span><input class="input" name="name" value="{{ old('name') }}" required maxlength="255"></label>
                <label class="field"><span>رقم الجوال *</span><input class="input" name="phone" value="{{ old('phone') }}" dir="ltr" inputmode="tel" placeholder="05XXXXXXXX" required></label>
                <label class="field"><span>رقم الهوية / الإقامة *</span><input class="input" name="national_id" value="{{ old('national_id') }}" dir="ltr" inputmode="numeric" maxlength="10" required></label>
                <label class="field"><span>تاريخ الميلاد</span><input class="input" type="date" name="birth_date" value="{{ old('birth_date') }}"></label>
                <label class="field"><span>البريد الإلكتروني</span><input class="input" type="email" name="email" value="{{ old('email') }}" dir="ltr"></label>
                <label class="field"><span>المؤهل</span><input class="input" name="qualification" value="{{ old('qualification') }}" maxlength="255" placeholder="ثانوية عامة، دبلوم…"></label>
                <label class="field"><span>سنوات الخبرة</span><input class="input num" type="number" name="experience_years" value="{{ old('experience_years') }}" min="0" max="60"></label>
                <label class="field"><span>كلمة المرور *</span><input class="input" type="password" name="password" dir="ltr" minlength="8" required autocomplete="new-password"></label>
                <label class="field"><span>تأكيد كلمة المرور *</span><input class="input" type="password" name="password_confirmation" dir="ltr" minlength="8" required autocomplete="new-password"></label>
                <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="3" maxlength="2000" placeholder="خبرة سابقة في الموانئ أو الأسواق…">{{ old('notes') }}</textarea></label>
            </div>
            <p class="card-sub" style="margin:0">نرسل رمز تحقق إلى جوالك بعد الإرسال. كلمة المرور هي التي تدخل بها التطبيق إن قُبلت.</p>
            <button class="btn btn-primary" type="submit">@include('partials.icon', ['name' => 'send']) إرسال الطلب</button>
        </form>
    @endif
@endsection

@push('scripts')
@if ($rounds->isNotEmpty())
<script>
    // الشجرة من الجولات المفتوحة وحدها: المنطقة تُصفّي المحافظات، والمحافظة تُصفّي الموانئ.
    const tree = @json($tree);
    const oldRound = @json((int) old('hiring_round_id'));
    const region = document.getElementById('ap-region');
    const governorate = document.getElementById('ap-governorate');
    const round = document.getElementById('ap-round');
    const info = document.getElementById('ap-round-info');

    function fill(select, values, placeholder) {
        select.innerHTML = '';
        select.append(new Option(placeholder, ''));
        values.forEach(([value, label]) => select.append(new Option(label, value)));
    }

    function unique(list) {
        return [...new Set(list)].map(v => [v, v]);
    }

    function onRegion() {
        fill(governorate, unique(tree.filter(r => r.region === region.value).map(r => r.governorate)), '— اختر —');
        onGovernorate();
    }

    function onGovernorate() {
        const rows = tree.filter(r => r.region === region.value && r.governorate === governorate.value);
        fill(round, rows.map(r => [r.round_id, r.port + ' — ' + r.company]), '— اختر الميناء —');
        onRound();
    }

    function onRound() {
        const row = tree.find(r => String(r.round_id) === round.value);
        info.hidden = !row;
        if (row) {
            info.textContent = `${row.title} · ${row.company} · بقي ${row.seats_left} مقعد · يُغلق التقديم ${row.closes_at}` + (row.notes ? ' — ' + row.notes : '');
        }
    }

    region.addEventListener('change', onRegion);
    governorate.addEventListener('change', onGovernorate);
    round.addEventListener('change', onRound);

    fill(region, unique(tree.map(r => r.region)), '— اختر —');
    const preset = tree.find(r => r.round_id === oldRound) ?? (tree.length === 1 ? tree[0] : null);
    if (preset) {
        region.value = preset.region; onRegion();
        governorate.value = preset.governorate; onGovernorate();
        round.value = preset.round_id; onRound();
    } else {
        onRegion();
    }
</script>
@if (\App\Rules\Recaptcha::enabled())
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}" async defer></script>
    <script>
        document.getElementById('apply-form').addEventListener('submit', function (event) {
            const form = event.target;
            if (form.dataset.verified) return;
            event.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            grecaptcha.ready(function () {
                grecaptcha.execute(@js(config('services.recaptcha.site_key')), { action: 'counter_apply' }).then(function (token) {
                    form.elements['g-recaptcha-response'].value = token;
                    form.dataset.verified = '1';
                    form.submit();
                }, function () { btn.disabled = false; });
            });
        });
    </script>
@endif
@endif
@endpush
