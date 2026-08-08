@extends('layouts.information-admin')

@section('title', 'أسواق السمك · سوق جديد')

@section('content')
<header class="info-admin-header info-admin-header-detail">
    <div>
        <a class="info-admin-back" href="{{ route('information.admin.markets.index') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6 6 6-6 6"></path><path d="M20 12H4"></path></svg>
            <span>العودة إلى أسواق السمك</span>
        </a>
        <h1>سوق سمك جديد</h1>
    </div>
</header>

<section class="info-form-card info-admin-panel">
    <div class="info-admin-panel-head">
        <h2>بيانات السوق والمستثمر</h2>
    </div>

    <x-information.market-form :regions="$regions" :governorates="$governorates" />
</section>
@endsection
