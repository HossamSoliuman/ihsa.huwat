@extends('layouts.information')

@section('title', 'بوابة تسجيل البيانات البحرية')

@section('content')
<section class="info-status-lookup" aria-labelledby="identity-title">
    <div class="info-form-card info-status-lookup-card">
        <h1 id="identity-title">ابدأ بتأكيد بياناتك</h1>
        <p class="info-status-lookup-lead">أدخل رقم الهوية ورقم الجوال للمتابعة.</p>

        <form method="post" action="{{ route('information.identity.store') }}" class="info-status-lookup-form">
            @csrf

            <div class="info-field">
                <label for="national_id">رقم الهوية / الإقامة <b>*</b></label>
                <input id="national_id" name="national_id" type="text" inputmode="numeric" dir="ltr"
                       value="{{ old('national_id') }}" placeholder="1xxxxxxxxx" autocomplete="off" required
                       aria-invalid="{{ $errors->has('national_id') ? 'true' : 'false' }}">
                @error('national_id')
                    <p class="info-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="info-field">
                <label for="phone">رقم الجوال <b>*</b></label>
                <input id="phone" name="phone" type="tel" inputmode="tel" dir="ltr"
                       value="{{ old('phone') }}" placeholder="05xxxxxxxx" autocomplete="off" required
                       aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}">
                @error('phone')
                    <p class="info-field-error">{{ $message }}</p>
                @enderror
            </div>

            <button class="info-button info-button-primary" type="submit">
                <span>متابعة</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6 6 6-6 6"></path><path d="M20 12H4"></path></svg>
            </button>
        </form>
    </div>
</section>
@endsection
