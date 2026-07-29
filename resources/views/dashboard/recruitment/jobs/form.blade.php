@extends('layouts.dashboard')

@section('title', $job->exists ? 'تعديل فرصة وظيفية' : 'فرصة وظيفية جديدة')

@section('content')
<div class="employment-admin-shell">
    <section class="employment-admin-hero"><div><span class="employment-eyebrow">{{ $job->exists ? $job->reference_no : 'فرصة جديدة' }}</span><h2>{{ $job->exists ? 'تعديل '.$job->title_ar : 'صياغة إعلان وظيفي' }}</h2><p>تُحفظ الفرصة كمسودة، ثم تُنشر من سجل الفرص بعد المراجعة.</p></div><a class="btn btn-outline" href="{{ route('dashboard.jobs.index') }}">العودة إلى الفرص</a></section>

    @if($job->status === 'archived')
        <div class="employment-inline-notice">هذه الفرصة في الأرشيف. استعدها كمسودة قبل تعديل بياناتها.</div>
    @else
        <section class="panel employment-editor"><form method="post" action="{{ $job->exists ? route('dashboard.jobs.update', $job) : route('dashboard.jobs.store') }}" class="employment-form-grid">
            @csrf @if($job->exists) @method('PUT') @endif
            <label class="span-2">المسمى الوظيفي <b>*</b><input name="title_ar" required maxlength="190" value="{{ old('title_ar', $job->title_ar) }}"></label>
            <label>الإدارة أو القسم<input name="department" maxlength="190" value="{{ old('department', $job->department) }}"></label>
            <label>نوع التوظيف <b>*</b><select name="employment_type" required>@foreach(config('employment.types') as $value => $label)<option value="{{ $value }}" @selected(old('employment_type', $job->employment_type ?: 'full_time') === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>عدد الشواغر <b>*</b><input type="number" name="vacancies" min="1" max="65535" required value="{{ old('vacancies', $job->vacancies ?: 1) }}"></label>
            <label>الميناء المرتبط<select name="port_id"><option value="">غير محدد</option>@foreach($ports as $port)<option value="{{ $port->id }}" @selected((string) old('port_id', $job->port_id) === (string) $port->id)>{{ $port->governorate->name }} — {{ $port->name }}</option>@endforeach</select></label>
            <label>المدينة<input name="city" maxlength="120" value="{{ old('city', $job->city) }}"></label>
            <label>آخر موعد للتقديم<input type="date" name="application_deadline" value="{{ old('application_deadline', $job->application_deadline?->format('Y-m-d')) }}"></label>
            <label>الراتب من<input type="number" name="salary_min" min="0" max="99999999.99" step="0.01" value="{{ old('salary_min', $job->salary_min) }}"></label>
            <label>الراتب إلى<input type="number" name="salary_max" min="0" max="99999999.99" step="0.01" value="{{ old('salary_max', $job->salary_max) }}"></label>
            <label class="span-2">الملخص <b>*</b><textarea name="summary" required rows="3">{{ old('summary', $job->summary) }}</textarea></label>
            <label class="span-2">الوصف <b>*</b><textarea name="description" required rows="7">{{ old('description', $job->description) }}</textarea></label>
            <label class="span-2">المهام والمسؤوليات<textarea name="responsibilities" rows="6">{{ old('responsibilities', $job->responsibilities) }}</textarea></label>
            <label class="span-2">المتطلبات <b>*</b><textarea name="requirements" required rows="7">{{ old('requirements', $job->requirements) }}</textarea></label>
            <div class="span-2 employment-form-actions"><button class="btn btn-primary" type="submit">حفظ الفرصة</button><a class="btn btn-outline" href="{{ route('dashboard.jobs.index') }}">إلغاء</a></div>
        </form></section>
    @endif
</div>
@endsection
