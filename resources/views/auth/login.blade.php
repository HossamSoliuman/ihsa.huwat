<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1d2835">
    <meta name="description" content="تسجيل الدخول إلى منصة حوات للخدمات والبيانات البحرية.">
    <link rel="icon" type="image/jpeg" href="{{ asset('assets/img/hud/ihsa-logo.jpeg') }}">
    <title>تسجيل الدخول | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="info-login-page font-sans antialiased">
    <main class="info-login-card">
        <div class="info-login-brand">
            <img src="{{ asset('assets/img/hud/hawat-logo.png') }}" alt="منصة حوات">
        </div>
        <h1 class="info-login-title">منصة الخدمات والبيانات البحرية</h1>

        @if (session('status'))
            <div class="info-alert info-alert-success" role="status">
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="info-alert info-alert-error" id="login_error" role="alert">
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form method="post" action="{{ route('login.store') }}" class="info-login-form">
            @csrf
            <div class="info-field">
                <label for="username">اسم المستخدم</label>
                <input id="username" name="username" value="{{ old('username') }}" maxlength="100" required autofocus autocomplete="username" @error('username') aria-invalid="true" aria-describedby="login_error" @enderror>
            </div>

            <div class="info-field">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" required autocomplete="current-password" @if($errors->any()) aria-describedby="login_error" @endif>
            </div>

            <button type="submit" class="info-button info-button-primary info-login-submit">دخول</button>
        </form>

        <p class="info-login-note">هذه المساحة مخصصة لموظفي إدخال البيانات المخولين.</p>
    </main>
</body>
</html>
