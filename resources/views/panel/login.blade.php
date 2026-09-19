@extends('layouts.auth')

@section('title', 'لوحة الإدارة')

@section('card')
    <div class="auth-brand">
        <div class="ico">@include('partials.icon', ['name' => 'layers'])</div>
        <div>
            <h1>لوحة الإدارة</h1>
            <p style="font-size:.74rem;color:hsl(var(--muted-foreground))">الملاك والدلالون والتجار ومركز المعلومات</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="auth-error">{{ $errors->first() }}</div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('panel.login.store') }}">
        @csrf

        <label class="field">
            <span>رقم الجوال أو البريد الإلكتروني</span>
            <input class="input" type="text" name="identifier" value="{{ old('identifier') }}"
                   dir="ltr" autocomplete="username" inputmode="email" placeholder="05XXXXXXXX" required autofocus>
        </label>

        <label class="field">
            <span>كلمة المرور</span>
            <input class="input" type="password" name="password"
                   dir="ltr" autocomplete="current-password" required>
        </label>

        <label class="auth-remember">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            تذكُّرني على هذا الجهاز
        </label>

        <button type="submit" class="btn btn-primary">
            @include('partials.icon', ['name' => 'log-in'])
            دخول
        </button>
    </form>
@endsection
