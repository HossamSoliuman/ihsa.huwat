@props([
    'index',
    'employee' => [],
    'idPrefix' => '',
    'jobTitles' => [],
    'nationalities' => [],
])

@php
    /**
     * One counted row of the دلال's عمالة. The rows are indexed by position, so the template
     * that adds a row on the page carries `__INDEX__` and the script settles the numbering.
     */
    $rowId = $idPrefix.'_employee_'.$index;
    $errorFor = fn (string $field): ?string => $errors->first("employees.{$index}.{$field}");
@endphp

<div class="info-field-grid info-field-grid-four info-employee-row" data-broker-employee-row>
    @php($error = $errorFor('job_title'))
    <div class="info-field">
        <label for="{{ $rowId }}_job_title">النوع <b aria-hidden="true">*</b></label>
        <select id="{{ $rowId }}_job_title" name="employees[{{ $index }}][job_title]"
                @if ($error) aria-invalid="true" aria-describedby="{{ $rowId }}_job_title_error" @endif>
            <option value="">اختر النوع</option>
            @foreach ($jobTitles as $code => $label)
                <option value="{{ $code }}" @selected((string) data_get($employee, 'job_title') === (string) $code)>{{ $label }}</option>
            @endforeach
        </select>
        @if ($error)<small id="{{ $rowId }}_job_title_error" class="info-field-error">{{ $error }}</small>@endif
    </div>

    @php($error = $errorFor('nationality'))
    <div class="info-field">
        <label for="{{ $rowId }}_nationality">الجنسية <b aria-hidden="true">*</b></label>
        <select id="{{ $rowId }}_nationality" name="employees[{{ $index }}][nationality]"
                @if ($error) aria-invalid="true" aria-describedby="{{ $rowId }}_nationality_error" @endif>
            <option value="">اختر الجنسية</option>
            @foreach ($nationalities as $code => $label)
                <option value="{{ $code }}" @selected((string) data_get($employee, 'nationality') === (string) $code)>{{ $label }}</option>
            @endforeach
        </select>
        @if ($error)<small id="{{ $rowId }}_nationality_error" class="info-field-error">{{ $error }}</small>@endif
    </div>

    @php($error = $errorFor('headcount'))
    <div class="info-field">
        <label for="{{ $rowId }}_headcount">العدد <b aria-hidden="true">*</b></label>
        <input id="{{ $rowId }}_headcount" name="employees[{{ $index }}][headcount]" type="number" dir="ltr"
               value="{{ data_get($employee, 'headcount') }}" min="1" max="9999" step="1" inputmode="numeric"
               @if ($error) aria-invalid="true" aria-describedby="{{ $rowId }}_headcount_error" @endif>
        @if ($error)<small id="{{ $rowId }}_headcount_error" class="info-field-error">{{ $error }}</small>@endif
    </div>

    <div class="info-field info-employee-row-action">
        <button type="button" class="info-repeat-remove" data-broker-remove-employee aria-label="حذف سجل الموظفين">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16m-10 4v6m4-6v6M9 7l1-3h4l1 3m-9 0 1 14h10l1-14"></path></svg>
            <span>حذف</span>
        </button>
    </div>
</div>
