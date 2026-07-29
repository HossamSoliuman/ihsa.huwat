@props(['harbor', 'boat', 'typeLabels', 'statusLabels'])
<form method="post" action="{{ $boat ? route('dashboard.harbors.boats.update', [$harbor, $boat]) : route('dashboard.harbors.boats.store', $harbor) }}" class="harbor-form-grid">
    @csrf @if($boat) @method('PUT') @endif
    <label>اسم القارب<input name="name" value="{{ old('name', $boat?->name) }}" maxlength="150" required></label>
    <label>رقم التسجيل<input name="registration_no" value="{{ old('registration_no', $boat?->registration_no) }}" maxlength="50"></label>
    <label>النوع<select name="boat_type">@foreach($typeLabels as $value => $label)<option value="{{ $value }}" @selected(old('boat_type', $boat?->boat_type ?? 'unclassified') === $value)>{{ $label }}</option>@endforeach</select></label>
    <label>الحالة<select name="harbor_status">@foreach($statusLabels as $value => $label)<option value="{{ $value }}" @selected(old('harbor_status', $boat?->harbor_status ?? 'unclassified') === $value)>{{ $label }}</option>@endforeach</select></label>
    <button class="btn btn-primary" type="submit">{{ $boat ? 'حفظ التعديل' : 'إضافة القارب' }}</button>
</form>
