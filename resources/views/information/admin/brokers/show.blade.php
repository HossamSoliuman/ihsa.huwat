@extends('layouts.information-admin')

@section('title', 'الدلالين · '.$broker->displayName())

@section('content')
<header class="info-admin-header info-admin-header-detail">
    <div>
        <a class="info-admin-back" href="{{ route('information.admin.brokers.index') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6 6 6-6 6"></path><path d="M20 12H4"></path></svg>
            <span>العودة إلى الدلالين</span>
        </a>
        <h1>{{ $broker->displayName() }}</h1>
        <p class="info-admin-header-meta">
            <span>{{ $broker->entityTypeLabel() }}</span>
            <span aria-hidden="true">·</span>
            <a href="{{ route('information.admin.markets.show', $broker->market) }}">{{ $broker->market->name }}</a>
            <span aria-hidden="true">·</span>
            <span>{{ $broker->market->governorate->region->name }} — {{ $broker->market->governorate->name }}</span>
        </p>
    </div>

    <span class="info-status-chip info-status-chip-lg" data-tone="{{ $broker->is_active ? 'sea' : 'gold' }}">
        <i aria-hidden="true"></i>{{ $broker->is_active ? 'نشط' : 'متوقف' }}
    </span>
</header>

<section class="info-form-card info-admin-panel" aria-labelledby="broker-details">
    <div class="info-admin-panel-head">
        <h2 id="broker-details">بيانات الدلال</h2>

        <form method="post" action="{{ route('information.admin.brokers.destroy', $broker) }}"
              data-confirm="سيُحذف سجل الدلال نهائياً. هل تريد المتابعة؟">
            @csrf
            @method('DELETE')
            <button class="info-lookup-action is-danger" type="submit">حذف الدلال</button>
        </form>
    </div>

    <x-information.broker-form :broker="$broker" :markets="$markets" :stalls="$stalls" :nationalities="$nationalities" :job-titles="$jobTitles" />
</section>
@endsection
