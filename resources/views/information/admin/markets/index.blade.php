@extends('layouts.information-admin')

@section('title', 'أسواق السمك')

@section('content')
<header class="info-admin-header">
    <div>
        <p class="info-eyebrow"><span></span>لوحة مركز المعلومات<span></span></p>
        <h1>أسواق السمك</h1>
    </div>

    <a class="info-button info-button-primary" href="{{ route('information.admin.markets.create') }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
        <span>سوق جديد</span>
    </a>
</header>

<section class="info-form-card info-admin-panel" aria-labelledby="markets-title">
    <div class="info-admin-panel-head">
        <h2 id="markets-title">قائمة الأسواق</h2>

        <form class="info-admin-filters" method="get" action="{{ route('information.admin.markets.index') }}">
            <div class="info-field">
                <label class="sr-only" for="filter-q">بحث</label>
                <input id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="ابحث باسم السوق أو المستثمر أو السجل التجاري">
            </div>

            <div class="info-field">
                <label class="sr-only" for="filter-region">المنطقة</label>
                <select id="filter-region" name="region_id">
                    <option value="">كل المناطق</option>
                    @foreach ($regions as $region)
                        <option value="{{ $region->id }}" @selected((int) ($filters['region_id'] ?? 0) === $region->id)>{{ $region->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="info-field">
                <label class="sr-only" for="filter-governorate">المحافظة</label>
                <select id="filter-governorate" name="governorate_id">
                    <option value="">كل المحافظات</option>
                    @foreach ($governorates as $governorate)
                        <option value="{{ $governorate->id }}" @selected((int) ($filters['governorate_id'] ?? 0) === $governorate->id)>{{ $governorate->name }}</option>
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
            <caption class="sr-only">أسواق السمك المسجلة</caption>
            <thead>
                <tr>
                    <th scope="col">السوق</th>
                    <th scope="col">المنطقة</th>
                    <th scope="col">المحافظة</th>
                    <th scope="col">المستثمر</th>
                    <th scope="col">المحلات</th>
                    <th scope="col">الدكات</th>
                    <th scope="col">العمالة</th>
                    <th scope="col">الدلالون</th>
                    <th scope="col">الحالة</th>
                    <th scope="col"><span class="sr-only">الإجراءات</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($markets as $market)
                    <tr @class(['is-retired' => ! $market->is_active])>
                        <td><strong>{{ $market->name }}</strong></td>
                        <td>{{ $market->governorate->region->name }}</td>
                        <td>{{ $market->governorate->name }}</td>
                        <td>
                            {{ $market->investor_name }}
                            <small dir="ltr">{{ $market->investor_commercial_registration_no }}</small>
                        </td>
                        <td>{{ $market->shops_count }}</td>
                        <td>{{ $market->auction_stalls_count }}</td>
                        <td>{{ $market->workers_count }}</td>
                        <td>{{ $market->brokers_count }}</td>
                        <td>
                            <span class="info-status-chip" data-tone="{{ $market->is_active ? 'sea' : 'gold' }}">
                                <i aria-hidden="true"></i>{{ $market->is_active ? 'نشط' : 'متوقف' }}
                            </span>
                        </td>
                        <td>
                            <a class="info-admin-row-action" href="{{ route('information.admin.markets.show', $market) }}"
                               aria-label="إدارة سوق {{ $market->name }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"></path><circle cx="12" cy="12" r="2.5"></circle></svg>
                                <span>إدارة</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="info-admin-empty">لا توجد أسواق مطابقة لعوامل التصفية الحالية.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($markets->hasPages())
        <div class="info-admin-pagination">{{ $markets->links() }}</div>
    @endif
</section>
@endsection
