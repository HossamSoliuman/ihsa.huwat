@props([
    'id',
    'name',
    'errorName',
    'label',
    'description',
    'required' => false,
    'compact' => false,
    'imageOnly' => false,
])

<div class="info-upload {{ $compact ? 'info-upload-compact' : '' }}" data-info-upload>
    <input
        type="file"
        id="{{ $id }}"
        name="{{ $name }}"
        accept="{{ $imageOnly ? '.jpg,.jpeg,.png,image/jpeg,image/png' : '.pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png' }}"
        data-info-file
        @required($required)
        aria-describedby="{{ $id }}_status{{ $errors->has($errorName) ? ' '.$id.'_error' : '' }}"
        @error($errorName) aria-invalid="true" @enderror
    >
    <label for="{{ $id }}">
        <span class="info-upload-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M14 3v5h5M5 3h9l5 5v13H5V3Zm7 15V11m-3 3 3-3 3 3"></path></svg>
        </span>
        <span class="info-upload-copy">
            <strong>{{ $label }} @if($required)<b aria-hidden="true">*</b>@endif</strong>
            <span>{{ $description }}</span>
            <span class="info-upload-button">اختيار ملف</span>
        </span>
    </label>
    <div class="info-upload-meta">
        <span id="{{ $id }}_status" data-info-file-name aria-live="polite">لم يتم اختيار ملف</span>
        <span>{{ $imageOnly ? 'JPG / PNG · 10MB' : 'PDF / JPG / PNG · 10MB' }}</span>
        <button type="button" class="info-upload-remove" data-info-file-remove hidden>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m-9 0 1 14h10l1-14"></path></svg>
            إزالة
        </button>
    </div>
</div>
@error($errorName)<small id="{{ $id }}_error" class="info-field-error info-upload-error">{{ $message }}</small>@enderror
