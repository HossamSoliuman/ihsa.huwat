@extends('layouts.dashboard')

@section('title', 'البيانات الأساسية')

@section('content')
@php($sections = ['regions' => 'المناطق', 'governorates' => 'المحافظات', 'ports' => 'الموانئ', 'boats' => 'القوارب', 'captains' => 'الكباتن', 'species' => 'أنواع الأسماك'])
<div class="employment-admin-shell master-data-shell">
    <section class="employment-admin-hero"><div><span class="employment-eyebrow">مرجع النظام</span><h2>البيانات الأساسية</h2><p>إدارة القيم المرجعية التي تعتمد عليها الرحلات والموانئ والتقارير.</p></div></section>
    <nav class="employment-workflow-nav master-data-tabs" aria-label="أقسام البيانات الأساسية">@foreach($sections as $key => $label)<a class="{{ $section === $key ? 'active' : '' }}" href="{{ route('dashboard.master-data.index', ['section' => $key]) }}">{{ $label }}</a>@endforeach</nav>

    <section class="panel master-data-editor">
        <h3>إضافة {{ $sections[$section] }}</h3>
        @if($section === 'regions')
            <form class="master-data-form" method="post" action="{{ route('dashboard.regions.store') }}">@csrf<label>اسم المنطقة<input name="name" value="{{ old('name') }}" maxlength="150" required></label><button class="btn btn-primary" type="submit">إضافة</button></form>
        @elseif($section === 'governorates')
            <form class="master-data-form" method="post" action="{{ route('dashboard.governorates.store') }}">@csrf<label>المنطقة<select name="region_id" required><option value="">اختر منطقة</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected((string) old('region_id') === (string) $region->id)>{{ $region->name }}</option>@endforeach</select></label><label>اسم المحافظة<input name="name" value="{{ old('name') }}" maxlength="150" required></label><button class="btn btn-primary" type="submit">إضافة</button></form>
        @elseif($section === 'ports')
            <form class="master-data-form" method="post" action="{{ route('dashboard.ports.store') }}">@csrf<label>المحافظة<select name="governorate_id" required><option value="">اختر محافظة</option>@foreach($governorates as $governorate)<option value="{{ $governorate->id }}" @selected((string) old('governorate_id') === (string) $governorate->id)>{{ $governorate->region->name }} — {{ $governorate->name }}</option>@endforeach</select></label><label>اسم الميناء<input name="name" value="{{ old('name') }}" maxlength="150" required></label><label>اسم الموقع<input name="location_name" value="{{ old('location_name') }}" maxlength="190"></label><label>رابط الموقع<input type="url" name="location_url" value="{{ old('location_url') }}" maxlength="500" dir="ltr"></label><label>خط العرض<input type="number" name="latitude" value="{{ old('latitude') }}" min="-90" max="90" step="0.000001"></label><label>خط الطول<input type="number" name="longitude" value="{{ old('longitude') }}" min="-180" max="180" step="0.000001"></label><label class="master-data-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))> نشط</label><button class="btn btn-primary" type="submit">إضافة</button></form>
        @elseif($section === 'boats')
            <form class="master-data-form" method="post" action="{{ route('dashboard.boats.store') }}">@csrf<label>اسم القارب<input name="name" value="{{ old('name') }}" maxlength="150" required></label><label>رقم التسجيل<input name="registration_no" value="{{ old('registration_no') }}" maxlength="50"></label><label>نوع القارب<select name="boat_type" required>@foreach(['large' => 'كبير', 'small' => 'صغير', 'recreational' => 'نزهة', 'unclassified' => 'غير مصنف'] as $value => $label)<option value="{{ $value }}" @selected(old('boat_type', 'unclassified') === $value)>{{ $label }}</option>@endforeach</select></label><label>حالة المرسى<select name="harbor_status" required>@foreach(['occupied' => 'مشغول', 'disabled' => 'متعطل', 'inactive' => 'غير نشط', 'unclassified' => 'غير مصنف'] as $value => $label)<option value="{{ $value }}" @selected(old('harbor_status', 'unclassified') === $value)>{{ $label }}</option>@endforeach</select></label><label>ميناء التسجيل<select name="home_port_id"><option value="">بدون</option>@foreach($ports as $port)<option value="{{ $port->id }}" @selected((string) old('home_port_id') === (string) $port->id)>{{ $port->name }}</option>@endforeach</select></label><button class="btn btn-primary" type="submit">إضافة</button></form>
        @elseif($section === 'captains')
            <form class="master-data-form" method="post" action="{{ route('dashboard.captains.store') }}">@csrf<label>الاسم الكامل<input name="full_name" value="{{ old('full_name') }}" maxlength="150" required></label><label>رقم الهوية<input name="national_id" value="{{ old('national_id') }}" maxlength="20"></label><label>الجوال<input name="phone" value="{{ old('phone') }}" maxlength="20" dir="ltr"></label><button class="btn btn-primary" type="submit">إضافة</button></form>
        @else
            <form class="master-data-form" method="post" action="{{ route('dashboard.species.store') }}">@csrf<label>اسم النوع<input name="name_ar" value="{{ old('name_ar') }}" maxlength="150" required></label><button class="btn btn-primary" type="submit">إضافة</button></form>
        @endif
    </section>

    <section class="panel employment-list-panel"><header class="employment-section-heading"><div><span>السجل المرجعي</span><h3>{{ $sections[$section] }} الحالية</h3></div><small>{{ $records->count() }} سجل</small></header>
        <div class="employment-table-wrap"><table class="employment-applications-table"><thead><tr>
            @if($section === 'regions')<th>الاسم</th><th>المحافظات</th>
            @elseif($section === 'governorates')<th>الاسم</th><th>المنطقة</th><th>الموانئ</th>
            @elseif($section === 'ports')<th>الاسم</th><th>المحافظة</th><th>الموقع</th><th>الحالة</th>
            @elseif($section === 'boats')<th>القارب</th><th>التسجيل</th><th>النوع</th><th>الميناء</th><th>الرحلات</th>
            @elseif($section === 'captains')<th>الاسم</th><th>الهوية</th><th>الجوال</th><th>الرحلات</th>
            @else<th>النوع</th><th>سجلات المصيد</th>@endif
            <th>الإجراءات</th></tr></thead><tbody>
        @forelse($records as $record)<tr>
            @if($section === 'regions')<td><strong>{{ $record->name }}</strong></td><td>{{ $record->governorates_count }}</td>
            @elseif($section === 'governorates')<td><strong>{{ $record->name }}</strong></td><td>{{ $record->region->name }}</td><td>{{ $record->ports_count }}</td>
            @elseif($section === 'ports')<td><strong>{{ $record->name }}</strong></td><td>{{ $record->governorate->name }}</td><td>{{ $record->location_name ?? '—' }}</td><td><span class="badge {{ $record->is_active ? 'badge-success' : 'badge-muted' }}">{{ $record->is_active ? 'نشط' : 'غير نشط' }}</span></td>
            @elseif($section === 'boats')<td><strong>{{ $record->name }}</strong></td><td>{{ $record->registration_no ?? '—' }}</td><td>{{ ['large' => 'كبير', 'small' => 'صغير', 'recreational' => 'نزهة', 'unclassified' => 'غير مصنف'][$record->boat_type] }}</td><td>{{ $record->homePort?->name ?? '—' }}</td><td>{{ $record->trips_count }}</td>
            @elseif($section === 'captains')<td><strong>{{ $record->full_name }}</strong></td><td>{{ $record->national_id ?? '—' }}</td><td dir="ltr">{{ $record->phone ?? '—' }}</td><td>{{ $record->trips_count }}</td>
            @else<td><strong>{{ $record->name_ar }}</strong></td><td>{{ $record->catch_details_count }}</td>@endif
            <td><div class="employment-row-actions">
                @if($section === 'ports')<form method="post" action="{{ route('dashboard.ports.toggle', $record) }}">@csrf @method('PATCH')<button class="btn btn-outline btn-sm" type="submit">{{ $record->is_active ? 'تعطيل' : 'تفعيل' }}</button></form>@endif
                @php($destroyRoute = match($section) { 'regions' => route('dashboard.regions.destroy', $record), 'governorates' => route('dashboard.governorates.destroy', $record), 'ports' => route('dashboard.ports.destroy', $record), 'boats' => route('dashboard.boats.destroy', $record), 'captains' => route('dashboard.captains.destroy', $record), default => route('dashboard.species.destroy', $record) })
                <form method="post" action="{{ $destroyRoute }}" data-confirm="سيُحذف هذا السجل نهائيًا إذا لم يكن مرتبطًا ببيانات أخرى. هل تريد المتابعة؟">@csrf @method('DELETE')<button class="btn btn-outline btn-sm" type="submit">حذف</button></form>
            </div></td>
        </tr>@empty<tr><td colspan="7">لا توجد سجلات في هذا القسم.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
