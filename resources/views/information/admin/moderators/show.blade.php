@extends('layouts.information-admin')

@section('title', 'المشرفون · '.$moderator->full_name)

@section('content')
<header class="info-admin-header info-admin-header-detail">
    <div>
        <a class="info-admin-back" href="{{ route('information.admin.moderators.index') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6 6 6-6 6"></path><path d="M20 12H4"></path></svg>
            <span>العودة إلى المشرفين</span>
        </a>
        <h1>{{ $moderator->full_name }}</h1>
        <p class="info-admin-header-meta">
            <span dir="ltr">{{ $moderator->username }}</span>
            @if ($moderator->last_login_at)
                <span aria-hidden="true">·</span>
                <span>آخر دخول {{ $moderator->last_login_at->diffForHumans() }}</span>
            @endif
        </p>
    </div>

    <span class="info-status-chip info-status-chip-lg" data-tone="{{ $moderator->is_active ? 'sea' : 'gold' }}">
        <i aria-hidden="true"></i>{{ $moderator->is_active ? 'نشط' : 'موقوف' }}
    </span>
</header>

<div class="info-admin-stack">
    <section class="info-form-card info-admin-panel">
        <div class="info-admin-panel-head">
            <h2>بيانات المشرف ونطاقه</h2>

            <form method="post" action="{{ route('information.admin.moderators.destroy', $moderator) }}"
                  data-confirm="سيحذف هذا الإجراء حساب المشرف نهائياً. هل تريد المتابعة؟">
                @csrf
                @method('DELETE')
                <button class="info-lookup-action is-danger" type="submit">حذف الحساب</button>
            </form>
        </div>

        <x-information.moderator-form
            :moderator="$moderator"
            :regions="$regions"
            :governorates="$governorates"
            :ports="$ports"
            :markets="$markets"
        />
    </section>
</div>
@endsection
