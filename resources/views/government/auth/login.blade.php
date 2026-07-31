<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1d2835">
    <title>تسجيل الدخول الحكومي | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/government.css') }}">
</head>
<body class="login-page">
    <main class="login-card government-login-card-simple">
        <div class="login-brand"><img class="login-brand-logo" src="{{ asset('assets/img/hud/hawat-logo.png') }}" alt="منصة حوات"></div>
        <h1 class="login-title">البوابة الحكومية</h1>

        @if ($errors->any())
            <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('government.login.store') }}">
            @csrf
            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" maxlength="100" autocomplete="username" autofocus required>
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="btn btn-primary btn-block" type="submit">دخول</button>
        </form>
    </main>
</body>
</html>
