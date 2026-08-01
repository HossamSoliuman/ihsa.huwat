<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/hud/ihsa-logo.jpeg') }}">
    <title>تهيئة النظام | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="login-brand"><span class="login-brand-logo" role="img" aria-label="Hawat"></span></div>
        <h1 class="login-title">تهيئة النظام لأول مرة</h1>
        <p class="login-sub">إنشاء حساب الإدارة العليا</p>
        @if ($configured)
            <div class="alert alert-success">النظام مهيأ بالفعل. <a href="{{ route('login') }}">تسجيل الدخول</a></div>
        @else
            @if ($errors->any())<div class="alert alert-error">{{ $errors->first() }}</div>@endif
            <form method="post" action="{{ route('setup.store') }}">
                @csrf
                <div class="form-group"><label for="full_name">الاسم الكامل</label><input id="full_name" name="full_name" value="{{ old('full_name') }}" required></div>
                <div class="form-group"><label for="username">اسم المستخدم</label><input id="username" name="username" value="{{ old('username') }}" required></div>
                <div class="form-group"><label for="password">كلمة المرور</label><input type="password" id="password" name="password" minlength="8" required></div>
                <div class="form-group"><label for="password_confirmation">تأكيد كلمة المرور</label><input type="password" id="password_confirmation" name="password_confirmation" minlength="8" required></div>
                <button type="submit" class="btn btn-primary btn-block">إنشاء الحساب</button>
            </form>
        @endif
    </main>
</body>
</html>
