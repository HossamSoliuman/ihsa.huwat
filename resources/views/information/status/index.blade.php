@extends('layouts.information')

@section('title', 'حالة الطلب '.$submission->reference_no)

@section('topbar-actions')
    <a class="info-button info-button-primary" href="{{ route('information.create') }}">
        <span>تسجيل طلب جديد</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
    </a>

    <form method="post" action="{{ route('information.identity.destroy') }}">
        @csrf
        <button class="info-button info-button-secondary" type="submit">إنهاء الجلسة</button>
    </form>
@endsection

@section('content')
<section class="info-status-page" aria-labelledby="status-title">
    <header class="info-status-page-head">
        <p class="info-eyebrow"><span></span>متابعة الطلب<span></span></p>
        <h1 id="status-title">حالة الطلب</h1>
    </header>

    @if ($submissions->count() > 1)
        <nav class="info-status-switcher" aria-label="طلباتك المسجلة">
            @foreach ($submissions as $option)
                <a class="info-status-switcher-item @if ($option->is($submission)) is-active @endif"
                   href="{{ route('information.status.index', ['reference' => $option->reference_no]) }}"
                   @if ($option->is($submission)) aria-current="page" @endif>
                    <span dir="ltr">{{ $option->reference_no }}</span>
                    <x-information.status-chip :status="$option->status" />
                </a>
            @endforeach
        </nav>
    @endif

    <div class="info-status-grid">
        <article class="info-form-card info-status-details" aria-labelledby="details-title">
            <h2 id="details-title">تفاصيل الطلب</h2>

            <dl class="info-status-details-list">
                <div><dt>رقم الطلب</dt><dd><strong class="info-admin-reference" dir="ltr">{{ $submission->reference_no }}</strong></dd></div>
                <div><dt>تاريخ الطلب</dt><dd>{{ $submission->submitted_at?->format('Y/m/d') }}</dd></div>
                <div><dt>القارب</dt><dd>{{ $submission->boat?->name ?? '—' }}</dd></div>
                <div><dt>الميناء</dt><dd>{{ $submission->port?->name ?? '—' }}</dd></div>
                <div><dt>عدد البحارة</dt><dd>{{ $submission->crew_count }}</dd></div>
                <div><dt>الحالة الحالية</dt><dd><x-information.status-chip :status="$submission->status" class="info-status-chip-lg" /></dd></div>
            </dl>

            @if ($submission->review_notes && in_array($submission->status, ['needs_edit', 'rejected'], true))
                <div class="info-status-notice" data-tone="{{ $submission->status === 'rejected' ? 'danger' : 'gold' }}">
                    <strong>{{ $submission->status === 'rejected' ? 'سبب الرفض' : 'التعديلات المطلوبة' }}</strong>
                    <p>{{ $submission->review_notes }}</p>
                </div>
            @endif
        </article>

        <article class="info-form-card info-status-timeline-card" aria-labelledby="timeline-title">
            <h2 id="timeline-title">مراحل الطلب</h2>

            <ol class="info-status-timeline">
                @foreach ($timeline as $milestone)
                    <li class="info-status-step" data-state="{{ $milestone['state'] }}">
                        <span class="info-status-step-marker" aria-hidden="true">
                            @if ($milestone['state'] === 'done')
                                <svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"></path></svg>
                            @elseif ($milestone['state'] === 'rejected')
                                <svg viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"></path></svg>
                            @endif
                        </span>
                        <div class="info-status-step-body">
                            <strong>{{ $milestone['label'] }}</strong>
                            @if ($milestone['at'])
                                <time datetime="{{ $milestone['at']->toIso8601String() }}">{{ $milestone['at']->format('Y/m/d — H:i') }}</time>
                            @else
                                <span class="info-status-step-pending">
                                    {{ $milestone['state'] === 'current' ? 'قيد التنفيذ' : 'لم تبدأ بعد' }}
                                </span>
                            @endif
                            @if ($milestone['note'])
                                <p>{{ $milestone['note'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>

            <p class="info-status-footnote">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Zm0-6v-4m0-4h.01"></path></svg>
                <span>في حال وجود ملاحظات سيتم إشعارك عبر الرسائل النصية على رقم الجوال المسجّل.</span>
            </p>
        </article>
    </div>
</section>
@endsection
