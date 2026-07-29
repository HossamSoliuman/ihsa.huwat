<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول | {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="login-brand"><span class="brand-icon" aria-hidden="true">⚓</span></div>
        <h1 class="login-title">{{ config('app.name') }}</h1>
        <p class="login-sub">الرجاء تسجيل الدخول للمتابعة</p>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('login.store') }}">
            @csrf
            <div class="form-group">
                <label for="username">اسم المستخدم</label>
                <input id="username" name="username" value="{{ old('username') }}" required autofocus autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">دخول</button>
        </form>
        <p class="login-public-link">تبحث عن فرصة عمل؟ <a href="{{ route('home') }}#available-jobs">تصفح الوظائف المتاحة</a></p>
    </main>
</body>
</html>
