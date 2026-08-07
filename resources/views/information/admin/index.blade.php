@extends('layouts.information-admin')

@section('title', 'لوحة مركز المعلومات · الطلبات')

@section('content')
<header class="info-admin-header">
    <div>
        <p class="info-eyebrow"><span></span>لوحة مركز المعلومات<span></span></p>
        <h1>الصيادين والبحارة</h1>
    </div>
</header>

<div class="info-admin-tiles">
    @php
        $tiles = [
            ['status' => null, 'label' => 'كل الطلبات', 'tone' => 'neutral'],
            ['status' => 'submitted', 'label' => 'جديد', 'tone' => 'sea'],
            ['status' => 'under_review', 'label' => 'تحت المراجعة', 'tone' => 'blue'],
            ['status' => 'needs_edit', 'label' => 'بانتظار التعديل', 'tone' => 'gold'],
            ['status' => 'approved', 'label' => 'معتمد', 'tone' => 'blue'],
            ['status' => 'rejected', 'label' => 'مرفوض', 'tone' => 'danger'],
        ];
    @endphp

    @foreach ($tiles as $tile)
        @php $key = $tile['status'] ?? 'all'; @endphp
        <a class="info-admin-tile @if (($filters['status'] ?? null) === $tile['status']) is-active @endif"
           data-tone="{{ $tile['tone'] }}"
           href="{{ route('information.admin.index', array_filter(['status' => $tile['status'], 'q' => $filters['q'] ?? null, 'port_id' => $filters['port_id'] ?? null])) }}">
            <strong>{{ $statusCounts[$key] ?? 0 }}</strong>
            <span>{{ $tile['label'] }}</span>
        </a>
    @endforeach
</div>

<section class="info-form-card info-admin-panel" aria-labelledby="submissions-title">
    <div class="info-admin-panel-head">
        <h2 id="submissions-title">قائمة الطلبات</h2>

        <form class="info-admin-filters" method="get" action="{{ route('information.admin.index') }}">
            <div class="info-field">
                <label class="sr-only" for="filter-q">بحث</label>
                <input id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="ابحث برقم الطلب أو اسم المالك أو الهوية">
            </div>

            <div class="info-field">
                <label class="sr-only" for="filter-status">الحالة</label>
                <select id="filter-status" name="status">
                    <option value="">كل الحالات</option>
                    @foreach (\App\Models\InformationSubmission::STATUS_LABELS as $statusKey => $statusLabel)
                        <option value="{{ $statusKey }}" @selected(($filters['status'] ?? null) === $statusKey)>{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>

            <div class="info-field">
                <label class="sr-only" for="filter-port">الميناء</label>
                <select id="filter-port" name="port_id">
                    <option value="">كل الموانئ</option>
                    @foreach ($ports as $port)
                        <option value="{{ $port->id }}" @selected((int) ($filters['port_id'] ?? 0) === $port->id)>{{ $port->name }}</option>
                    @endforeach
                </select>
            </div>

            <button class="info-button info-button-primary" type="submit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14Zm5.5-1.5L21 21"></path></svg>
                <span>تصفية</span>
            </button>
        </form>
    </div>

    <div class="info-admin-table-scroll">
        <table class="info-admin-table">
            <caption class="sr-only">قائمة طلبات تسجيل البيانات البحرية</caption>
            <thead>
                <tr>
                    <th scope="col">رقم الطلب</th>
                    <th scope="col">اسم المالك</th>
                    <th scope="col">القارب</th>
                    <th scope="col">الميناء</th>
                    <th scope="col">تاريخ الإرسال</th>
                    <th scope="col">الحالة</th>
                    <th scope="col"><span class="sr-only">الإجراءات</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($submissions as $submission)
                    <tr>
                        <td><span class="info-admin-reference" dir="ltr">{{ $submission->reference_no }}</span></td>
                        <td>{{ $submission->owner_full_name }}</td>
                        <td>
                            {{ $submission->boat?->name ?? '—' }}
                            @if ($submission->boat?->registration_no)
                                <small dir="ltr">{{ $submission->boat->registration_no }}</small>
                            @endif
                        </td>
                        <td>{{ $submission->port?->name ?? '—' }}</td>
                        <td><time datetime="{{ $submission->submitted_at?->toIso8601String() }}">{{ $submission->submitted_at?->format('Y/m/d') }}</time></td>
                        <td><x-information.status-chip :status="$submission->status" /></td>
                        <td>
                            <a class="info-admin-row-action" href="{{ route('information.admin.show', $submission) }}"
                               aria-label="مراجعة الطلب {{ $submission->reference_no }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                <span>مراجعة</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="info-admin-empty">لا توجد طلبات مطابقة لعوامل التصفية الحالية.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($submissions->hasPages())
        <div class="info-admin-pagination">{{ $submissions->links() }}</div>
    @endif
</section>
@endsection
