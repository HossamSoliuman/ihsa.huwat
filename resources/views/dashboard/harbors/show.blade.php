@extends('layouts.dashboard')

@section('title', 'إدارة المرافئ')
@section('body-class', 'harbor-details-page')
@push('styles')<link rel="stylesheet" href="{{ asset('assets/css/harbor.css') }}">@endpush

@section('content')
@php
    $tabs = ['overview' => 'نظرة عامة', 'boats' => 'القوارب', 'workers' => 'القوى البشرية', 'licenses' => 'التراخيص', 'violations' => 'المخالفات'];
    $boatTypeLabels = ['large' => 'كبير', 'small' => 'صغير', 'recreational' => 'نزهة', 'unclassified' => 'غير مصنف'];
    $boatStatusLabels = ['occupied' => 'شاغل', 'disabled' => 'معطل', 'inactive' => 'غير نشط', 'unclassified' => 'غير مصنف'];
    $workerTypeLabels = ['supervisor' => 'مشرف', 'contractor' => 'متعاقد', 'fisherman' => 'صياد', 'foreign_worker' => 'عامل أجنبي'];
    $employmentLabels = ['active' => 'نشط', 'suspended' => 'موقوف', 'expired' => 'منتهي'];
    $licenseStatusLabels = ['valid' => 'سارية', 'expired' => 'منتهية', 'suspended' => 'معلقة', 'cancelled' => 'ملغاة'];
    $violationStatusLabels = ['open' => 'مفتوحة', 'paid' => 'مسددة', 'appealed' => 'معترض عليها', 'closed' => 'مغلقة'];
@endphp
<div class="harbor-workspace">
@if($ports->isNotEmpty())
    <section class="harbor-scope-selector">
        <header><div><span>مسار الوصول</span><h2>اختيار المرفأ</h2></div><small>المحافظة ← المدينة ← المرفأ</small></header>
        <form method="get" action="{{ route('dashboard.harbors.index') }}" data-harbor-selector>
            <label><span><b>01</b> المحافظة</span><select name="region_id" data-harbor-region required><option value="">اختر المحافظة</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected((string) ($selection['region_id'] ?? '') === (string) $region->id)>{{ $region->name }}</option>@endforeach</select>@error('region_id')<small>{{ $message }}</small>@enderror</label>
            <i aria-hidden="true"></i>
            <label><span><b>02</b> المدينة</span><select name="governorate_id" data-harbor-city required><option value="">اختر المدينة</option>@foreach($selectorGovernorates as $governorate)<option value="{{ $governorate->id }}" data-region-id="{{ $governorate->region_id }}" @selected((string) ($selection['governorate_id'] ?? '') === (string) $governorate->id)>{{ $governorate->name }}</option>@endforeach</select>@error('governorate_id')<small>{{ $message }}</small>@enderror</label>
            <i aria-hidden="true"></i>
            <label><span><b>03</b> المرفأ</span><select name="port_id" data-harbor-port required><option value="">اختر المرفأ</option>@foreach($ports as $port)<option value="{{ $port->id }}" data-region-id="{{ $port->governorate->region_id }}" data-governorate-id="{{ $port->governorate_id }}" @selected((string) ($selection['port_id'] ?? '') === (string) $port->id)>{{ $port->name }}{{ $port->is_active ? '' : ' — غير نشط' }}</option>@endforeach</select>@error('port_id')<small>{{ $message }}</small>@enderror</label>
            <button class="btn btn-primary" type="submit" data-harbor-submit>عرض بيانات المرفأ</button>
        </form>
    </section>
@endif

@if($ports->isEmpty())
    <section class="harbor-empty"><span class="harbor-kicker">سجل المرافئ</span><h1>لا يوجد مرفأ ضمن نطاق صلاحيتك</h1><p>أنشئ أول مرفأ لبدء تسجيل الطاقة الاستيعابية والقوارب والقوى البشرية والتراخيص.</p>
        @can('create', App\Models\Port::class)<details class="harbor-create-drawer" open><summary>إنشاء مرفأ جديد</summary><form method="post" action="{{ route('dashboard.harbors.store') }}" class="harbor-form-grid">@csrf
            <label>اسم المرفأ<input name="name" required maxlength="150"></label><label>المحافظة<select name="governorate_id" required>@foreach($governorates as $governorate)<option value="{{ $governorate->id }}">{{ $governorate->region->name }} — {{ $governorate->name }}</option>@endforeach</select></label><label>اسم الموقع<input name="location_name" maxlength="190"></label><label>رابط الموقع<input type="url" name="location_url" maxlength="500" dir="ltr"></label><label>خط العرض<input type="number" name="latitude" step="0.000001" min="-90" max="90"></label><label>خط الطول<input type="number" name="longitude" step="0.000001" min="-180" max="180"></label><label class="harbor-check"><input type="checkbox" name="is_active" value="1" checked> نشط</label><button class="btn btn-primary" type="submit">إنشاء المرفأ</button>
        </form></details>@endcan
    </section>
@elseif(!$harbor)
    <section class="harbor-empty harbor-selection-empty"><span class="harbor-kicker">دليل المرافئ</span><h1>اختر المرفأ المطلوب</h1><p>ابدأ بالمحافظة، ثم المدينة، ثم المرفأ لعرض جميع بياناته وإدارتها.</p></section>
@else
    <header class="harbor-commandbar"><div class="harbor-titleblock"><div class="harbor-kicker"><span>إدارة المرافئ</span><b>HBR–{{ str_pad((string) $harbor->id, 4, '0', STR_PAD_LEFT) }}</b></div><h1>{{ $harbor->name }}</h1><p>{{ $harbor->governorate->region->name }} · {{ $harbor->governorate->name }} @if($harbor->location_name)· {{ $harbor->location_name }}@endif</p></div><div class="harbor-command-actions">
        <a class="harbor-icon-button" href="{{ route('dashboard.harbors.export', $harbor) }}" title="تصدير CSV" aria-label="تصدير CSV">⇩</a>
    </div></header>

    <section class="harbor-vitals"><article><span>الطاقة الكلية</span><strong>{{ $boatTypes->sum('capacity') }}</strong><small>قارب</small></article><article><span>القوارب الشاغلة</span><strong>{{ $boatTypes->sum('occupied') }}</strong><small>{{ $boatTypes->sum('capacity') > 0 ? round($boatTypes->sum('occupied') / $boatTypes->sum('capacity') * 100, 1) : 0 }}%</small></article><article><span>العاملون النشطون</span><strong>{{ $harbor->harborWorkers->where('employment_status', 'active')->count() }}</strong></article><article><span>الرخص السارية</span><strong>{{ $harbor->harborLicenses->where('license_status', 'valid')->count() }}</strong></article><article class="harbor-alert-vital"><span>مخالفات مفتوحة</span><strong>{{ $harbor->harborViolations->whereIn('violation_status', ['open', 'appealed'])->count() }}</strong></article></section>
    <nav class="harbor-tabs" aria-label="أقسام المرفأ">@foreach($tabs as $key => $label)<a class="{{ $tab === $key ? 'active' : '' }}" href="{{ route('dashboard.harbors.show', ['port' => $harbor, 'tab' => $key]) }}">{{ $label }}</a>@endforeach</nav>

    @if($tab === 'overview')
        <section class="harbor-section-grid"><article class="harbor-panel harbor-capacity-panel"><header><div><span>الطاقة الاستيعابية</span><h2>حالة الأرصفة</h2></div></header><div class="harbor-capacity-rings">@foreach($boatTypes as $key => $type)<div class="harbor-capacity-card"><div class="harbor-ring"><strong>{{ $type['occupied'] }}</strong><small>/ {{ $type['capacity'] }}</small><progress max="100" value="{{ $type['percent'] }}">{{ $type['percent'] }}%</progress></div><h3>{{ $type['label'] }}</h3><p>{{ $type['available'] }} متاح · {{ $type['disabled'] }} معطل</p><span class="harbor-state state-{{ $type['status'] }}">{{ ['available' => 'متاح', 'full' => 'ممتلئ', 'stopped' => 'متوقف'][$type['status']] }}</span></div>@endforeach</div>
            <details class="harbor-inline-editor"><summary>تعديل الطاقة الاستيعابية</summary><form method="post" action="{{ route('dashboard.harbors.capacities.update', $harbor) }}" class="harbor-form-grid">@csrf @method('PUT')@foreach($boatTypes as $key => $type)<fieldset><legend>{{ $type['label'] }}</legend><label>السعة<input type="number" name="capacities[{{ $key }}][capacity]" value="{{ $type['capacity'] }}" min="0" required></label><label>الحالة<select name="capacities[{{ $key }}][status]"><option value="available" @selected($type['status'] !== 'stopped')>متاح</option><option value="stopped" @selected($type['status'] === 'stopped')>متوقف</option></select></label></fieldset>@endforeach<button class="btn btn-primary" type="submit">حفظ السعات</button></form></details>
        </article><article class="harbor-panel"><header><div><span>بطاقة المرفأ</span><h2>البيانات الجغرافية</h2></div></header><dl class="harbor-facts"><div><dt>المنطقة</dt><dd>{{ $harbor->governorate->region->name }}</dd></div><div><dt>المحافظة</dt><dd>{{ $harbor->governorate->name }}</dd></div><div><dt>الموقع</dt><dd>{{ $harbor->location_name ?? 'غير محدد' }}</dd></div><div><dt>الإحداثيات</dt><dd dir="ltr">{{ $harbor->latitude ?? '—' }}, {{ $harbor->longitude ?? '—' }}</dd></div><div><dt>الحالة</dt><dd>{{ $harbor->is_active ? 'نشط' : 'غير نشط' }}</dd></div></dl>
            <details class="harbor-inline-editor"><summary>تعديل بيانات المرفأ</summary><form method="post" action="{{ route('dashboard.harbors.update', $harbor) }}" class="harbor-form-grid">@csrf @method('PUT')<label>الاسم<input name="name" value="{{ $harbor->name }}" required maxlength="150"></label><label>المحافظة<select name="governorate_id">@foreach($governorates as $governorate)<option value="{{ $governorate->id }}" @selected($harbor->governorate_id === $governorate->id)>{{ $governorate->name }}</option>@endforeach</select></label><label>الموقع<input name="location_name" value="{{ $harbor->location_name }}"></label><label>رابط الموقع<input type="url" name="location_url" value="{{ $harbor->location_url }}" dir="ltr"></label><label>خط العرض<input type="number" name="latitude" value="{{ $harbor->latitude }}" step="0.000001"></label><label>خط الطول<input type="number" name="longitude" value="{{ $harbor->longitude }}" step="0.000001"></label><label class="harbor-check"><input type="checkbox" name="is_active" value="1" @checked($harbor->is_active)> نشط</label><button class="btn btn-primary" type="submit">حفظ</button></form></details>
        </article></section>
    @elseif($tab === 'boats')
        <section class="harbor-panel"><header><div><span>أسطول المرفأ</span><h2>القوارب المسجلة</h2></div><strong>{{ $harbor->boats->count() }}</strong></header><details class="harbor-create-drawer"><summary>إضافة قارب</summary><x-harbor.boat-form :harbor="$harbor" :boat="null" :type-labels="$boatTypeLabels" :status-labels="$boatStatusLabels" /></details><x-harbor.boat-table :harbor="$harbor" :type-labels="$boatTypeLabels" :status-labels="$boatStatusLabels" /></section>
    @elseif($tab === 'workers')
        <section class="harbor-panel"><header><div><span>القوى البشرية</span><h2>العاملون في المرفأ</h2></div><strong>{{ $harbor->harborWorkers->count() }}</strong></header><details class="harbor-create-drawer"><summary>إضافة عامل</summary><x-harbor.worker-form :harbor="$harbor" :worker="null" :type-labels="$workerTypeLabels" :status-labels="$employmentLabels" /></details><x-harbor.worker-table :harbor="$harbor" :type-labels="$workerTypeLabels" :status-labels="$employmentLabels" /></section>
    @elseif($tab === 'licenses')
        <section class="harbor-panel"><header><div><span>الامتثال</span><h2>التراخيص</h2></div><strong>{{ $harbor->harborLicenses->count() }}</strong></header><details class="harbor-create-drawer"><summary>تسجيل رخصة</summary><x-harbor.license-form :harbor="$harbor" :license="null" :status-labels="$licenseStatusLabels" /></details><x-harbor.license-table :harbor="$harbor" :status-labels="$licenseStatusLabels" /></section>
    @else
        <section class="harbor-panel"><header><div><span>الرقابة</span><h2>المخالفات</h2></div><strong>{{ $harbor->harborViolations->count() }}</strong></header><details class="harbor-create-drawer"><summary>تسجيل مخالفة</summary><x-harbor.violation-form :harbor="$harbor" :violation="null" :status-labels="$violationStatusLabels" /></details><x-harbor.violation-table :harbor="$harbor" :status-labels="$violationStatusLabels" /></section>
    @endif
@endif
</div>
@endsection
