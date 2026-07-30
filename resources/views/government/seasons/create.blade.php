@extends('government.layouts.app')

@section('title', 'إنشاء موسم صيد')
@section('body-class', 'government-portal-page government-season-form-page')

@section('content')
<div class="government-shell government-form-shell">
    <header class="government-commandbar panel">
        <div>
            <span class="government-eyebrow">GOV // NEW SEASON</span>
            <h1>إنشاء موسم صيد جديد</h1>
            <p>حدد النطاق الزمني والمنطقة والأدوات والقيود التي تنظّم العمل خلال الموسم.</p>
        </div>
        <div class="government-command-actions"><a class="btn btn-outline" href="{{ route('government.seasons.index') }}">العودة إلى السجل</a></div>
    </header>

    <form class="panel government-season-form" method="POST" action="{{ route('government.seasons.store') }}">
        @csrf
        <div class="government-section-heading"><div><span>REQUIRED DATA</span><h2>بيانات الموسم</h2></div><small>الحقول المعلّمة * مطلوبة</small></div>

        <div class="government-form-grid">
            <label class="government-field">اسم الموسم *<input name="name" type="text" maxlength="120" value="{{ old('name') }}" placeholder="مثال: موسم صيد الروبيان" required>@error('name')<span class="government-field-error">{{ $message }}</span>@enderror</label>
            <label class="government-field">الحالة *<select name="status" required><option value="">اختر الحالة</option>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(old('status') === $value)>{{ $label }}</option>@endforeach</select>@error('status')<span class="government-field-error">{{ $message }}</span>@enderror</label>
            <label class="government-field">المنطقة *<select name="region_id" required><option value="">اختر المنطقة</option>@foreach($regions as $region)<option value="{{ $region->id }}" @selected((string) old('region_id') === (string) $region->id)>{{ $region->name }}</option>@endforeach</select>@error('region_id')<span class="government-field-error">{{ $message }}</span>@enderror</label>
            <label class="government-field">تاريخ البداية *<input name="start_date" type="date" value="{{ old('start_date') }}" required>@error('start_date')<span class="government-field-error">{{ $message }}</span>@enderror</label>
            <label class="government-field">تاريخ النهاية *<input name="end_date" type="date" value="{{ old('end_date') }}" required>@error('end_date')<span class="government-field-error">{{ $message }}</span>@enderror</label>
            <label class="government-field">عدد الرخص الموسمية *<input name="licenses_count" type="number" min="0" value="{{ old('licenses_count', 0) }}" required>@error('licenses_count')<span class="government-field-error">{{ $message }}</span>@enderror</label>
            <label class="government-field">الحد الأدنى للقياس (سم)<input name="minimum_size" type="number" min="0" step="0.01" value="{{ old('minimum_size') }}" placeholder="10.5">@error('minimum_size')<span class="government-field-error">{{ $message }}</span>@enderror</label>
            <label class="government-field">الحد الأعلى للقياس (سم)<input name="maximum_size" type="number" min="0" step="0.01" value="{{ old('maximum_size') }}" placeholder="25.0">@error('maximum_size')<span class="government-field-error">{{ $message }}</span>@enderror</label>

            <fieldset class="government-tool-field government-form-span">
                <legend>أدوات الصيد المسموحة *</legend>
                <div class="government-tool-grid">
                    @foreach($fishingTools as $fishingTool)
                        <label><input name="fishing_tools[]" type="checkbox" value="{{ $fishingTool }}" @checked(in_array($fishingTool, old('fishing_tools', []), true))><span>{{ $fishingTool }}</span></label>
                    @endforeach
                </div>
                @error('fishing_tools')<span class="government-field-error">{{ $message }}</span>@enderror
            </fieldset>

            <label class="government-field government-form-span">القيود التنظيمية *<textarea name="restrictions" maxlength="2000" rows="6" placeholder="اكتب الاشتراطات والقيود المطبقة خلال الموسم" required>{{ old('restrictions') }}</textarea>@error('restrictions')<span class="government-field-error">{{ $message }}</span>@enderror</label>
        </div>

        <footer class="government-form-actions">
            <a class="btn btn-outline" href="{{ route('government.seasons.index') }}">إلغاء</a>
            <button class="btn btn-primary" type="submit">حفظ الموسم</button>
        </footer>
    </form>
</div>
@endsection
