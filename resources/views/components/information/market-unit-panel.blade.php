@props([
    'market',
    'type',
    'units',
    'nationalities' => [],
    'jobTitles' => [],
    'nationalityLabels' => [],
    'jobTitleLabels' => [],
])

@php
    $typeLabel = \App\Models\FishMarketUnit::TYPE_LABELS[$type];
    $title = $type === \App\Models\FishMarketUnit::TYPE_SHOP ? 'محلات بيع السمك' : 'دكات الحراجات';
    $workerCount = $units->sum(fn (\App\Models\FishMarketUnit $unit): int => $unit->workers->count());
    /** Records opened by the market's counts and still waiting for their paperwork. */
    $pendingCount = $units->filter(fn (\App\Models\FishMarketUnit $unit): bool => $unit->awaitsDetails())->count();
@endphp

<section class="info-form-card info-admin-panel" aria-labelledby="units-{{ $type }}">
    <div class="info-admin-panel-head">
        <h2 id="units-{{ $type }}">{{ $title }}</h2>
        <small class="info-lookup-count">
            {{ $units->count() }} سجل · {{ $workerCount }} عامل
            @if ($pendingCount) · {{ $pendingCount }} بانتظار استكمال البيانات @endif
        </small>
    </div>

    <details class="info-admin-drawer" {{ old('form_key') === 'unit-new-'.$type ? 'open' : '' }}>
        <summary>إضافة {{ $typeLabel }}</summary>
        <x-information.market-unit-form :market="$market" :type="$type" />
    </details>

    @forelse ($units as $unit)
        @php
            $isOpen = old('form_key') === 'unit-'.$unit->id
                || old('form_key') === 'worker-new-'.$unit->id
                || $unit->workers->contains(fn (\App\Models\FishMarketWorker $worker): bool => old('form_key') === 'worker-'.$worker->id);
        @endphp

        <details class="info-admin-record" {{ $isOpen ? 'open' : '' }}>
            <summary>
                <span class="info-admin-record-title">
                    <strong>{{ $unit->label ?? $typeLabel }}</strong>
                    @if ($unit->awaitsDetails())
                        <small>بانتظار استكمال البيانات</small>
                    @else
                        <small>{{ $unit->entity_name }} · سجل تجاري <span dir="ltr">{{ $unit->commercial_registration_no }}</span></small>
                    @endif
                </span>
                <span class="info-status-chip" data-tone="{{ $unit->is_active ? 'sea' : 'gold' }}">
                    <i aria-hidden="true"></i>{{ $unit->is_active ? 'نشط' : 'متوقف' }}
                </span>
                <span class="info-lookup-count">{{ $unit->workers->count() }} عامل</span>
            </summary>

            <div class="info-admin-record-body">
                <div class="info-admin-subhead">
                    <h3>بيانات {{ $typeLabel }}</h3>

                    <form method="post" action="{{ route('information.admin.markets.units.destroy', [$market, $unit]) }}"
                          data-confirm="سيُحذف هذا السجل نهائياً إذا لم تكن عليه عمالة مسجلة. هل تريد المتابعة؟">
                        @csrf
                        @method('DELETE')
                        <button class="info-lookup-action is-danger" type="submit">حذف {{ $typeLabel }}</button>
                    </form>
                </div>

                <x-information.market-unit-form :market="$market" :type="$type" :unit="$unit" />

                <div class="info-admin-subhead">
                    <h3>العمالة</h3>
                    <small class="info-lookup-count">{{ $unit->workers->count() }} سجل</small>
                </div>

                <div class="info-admin-table-scroll">
                    <table class="info-admin-table">
                        <caption class="sr-only">العمالة المسجلة على {{ $unit->label ?? $typeLabel }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">الاسم</th>
                                <th scope="col">رقم الهوية</th>
                                <th scope="col">رقم التلفون</th>
                                <th scope="col">البريد الإلكتروني</th>
                                <th scope="col">الجنسية</th>
                                <th scope="col">الوظيفة</th>
                                <th scope="col"><span class="sr-only">الإجراءات</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($unit->workers as $worker)
                                <tr>
                                    <td>{{ $worker->full_name }}</td>
                                    <td><span dir="ltr">{{ $worker->national_id }}</span></td>
                                    <td><span dir="ltr">{{ $worker->phone ?? '—' }}</span></td>
                                    <td><span dir="ltr">{{ $worker->email ?? '—' }}</span></td>
                                    <td>{{ $nationalityLabels[$worker->nationality] ?? $worker->nationality }}</td>
                                    <td>{{ $jobTitleLabels[$worker->job_title] ?? $worker->job_title }}</td>
                                    <td>
                                        <div class="info-lookup-actions">
                                            <form method="post" action="{{ route('information.admin.markets.units.workers.destroy', [$market, $unit, $worker]) }}"
                                                  data-confirm="سيُحذف سجل العامل نهائياً. هل تريد المتابعة؟">
                                                @csrf
                                                @method('DELETE')
                                                <button class="info-lookup-action is-danger" type="submit">حذف</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="info-admin-inline-row">
                                    <td colspan="7">
                                        <details {{ old('form_key') === 'worker-'.$worker->id ? 'open' : '' }}>
                                            <summary>تعديل بيانات {{ $worker->full_name }}</summary>
                                            <x-information.market-worker-form
                                                :market="$market" :unit="$unit" :worker="$worker"
                                                :nationalities="$nationalities" :job-titles="$jobTitles"
                                            />
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="info-admin-empty">لا توجد عمالة مسجلة على هذا السجل.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <details class="info-admin-drawer" {{ old('form_key') === 'worker-new-'.$unit->id ? 'open' : '' }}>
                    <summary>إضافة عامل</summary>
                    <x-information.market-worker-form
                        :market="$market" :unit="$unit"
                        :nationalities="$nationalities" :job-titles="$jobTitles"
                    />
                </details>
            </div>
        </details>
    @empty
        <p class="info-admin-empty">لم تُسجَّل {{ $title }} بعد.</p>
    @endforelse
</section>
