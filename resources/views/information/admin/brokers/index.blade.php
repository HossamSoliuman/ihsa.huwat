@extends('layouts.information-admin')

@section('title', 'الدلالين')

@section('content')
<header class="info-admin-header">
    <div>
        <p class="info-eyebrow"><span></span>لوحة مركز المعلومات<span></span></p>
        <h1>الدلالين</h1>
    </div>

    <a class="info-button info-button-primary" href="{{ route('information.admin.brokers.create') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
        <span>دلال جديد</span>
    </a>
</header>

<section class="info-form-card info-admin-panel" aria-labelledby="brokers-title">
    <div class="info-admin-panel-head">
        <h2 id="brokers-title">قائمة الدلالين</h2>

        <form class="info-admin-filters" method="get" action="{{ route('information.admin.brokers.index') }}">
            <div class="info-field">
                <label class="sr-only" for="filter-q">بحث</label>
                <input id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="ابحث بالاسم أو المؤسسة أو السجل التجاري أو الجوال">
            </div>

            <div class="info-field">
                <label class="sr-only" for="filter-market">السوق</label>
                <select id="filter-market" name="fish_market_id">
                    <option value="">كل الأسواق</option>
                    @foreach ($markets as $market)
                        <option value="{{ $market->id }}" @selected((int) ($filters['fish_market_id'] ?? 0) === $market->id)>
                            {{ $market->governorate->name }} — {{ $market->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="info-field">
                <label class="sr-only" for="filter-entity-type">نوع الدلال</label>
                <select id="filter-entity-type" name="entity_type">
                    <option value="">كل الأنواع</option>
                    @foreach (\App\Models\FishMarketBroker::ENTITY_TYPE_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['entity_type'] ?? null) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="info-field">
                <label class="sr-only" for="filter-status">الحالة</label>
                <select id="filter-status" name="status">
                    <option value="">كل الحالات</option>
                    <option value="active" @selected(($filters['status'] ?? null) === 'active')>نشط</option>
                    <option value="inactive" @selected(($filters['status'] ?? null) === 'inactive')>متوقف</option>
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
            <caption class="sr-only">الدلالون المسجلون</caption>
            <thead>
                <tr>
                    <th scope="col">الدلال</th>
                    <th scope="col">النوع</th>
                    <th scope="col">السوق</th>
                    <th scope="col">رقم الجوال</th>
                    <th scope="col">السجل التجاري</th>
                    <th scope="col">الحالة</th>
                    <th scope="col"><span class="sr-only">الإجراءات</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($brokers as $broker)
                    <tr @class(['is-retired' => ! $broker->is_active])>
                        <td><strong>{{ $broker->displayName() }}</strong></td>
                        <td>{{ $broker->entityTypeLabel() }}</td>
                        <td>
                            {{ $broker->market->name }}
                            <small>{{ $broker->market->governorate->name }}</small>
                        </td>
                        <td><span dir="ltr">{{ $broker->phone ?? '—' }}</span></td>
                        <td><span dir="ltr">{{ $broker->commercial_registration_no ?? '—' }}</span></td>
                        <td>
                            <span class="info-status-chip" data-tone="{{ $broker->is_active ? 'sea' : 'gold' }}">
                                <i aria-hidden="true"></i>{{ $broker->is_active ? 'نشط' : 'متوقف' }}
                            </span>
                        </td>
                        <td>
                            <a class="info-admin-row-action" href="{{ route('information.admin.brokers.show', $broker) }}"
                               aria-label="تعديل بيانات {{ $broker->displayName() }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"></path><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                                <span>تعديل</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="info-admin-empty">لا يوجد دلالون مطابقون لعوامل التصفية الحالية.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($brokers->hasPages())
        <div class="info-admin-pagination">{{ $brokers->links() }}</div>
    @endif
</section>
@endsection
