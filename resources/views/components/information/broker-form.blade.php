@props([
    'broker' => null,
    'markets' => [],
    'stalls' => [],
    'nationalities' => [],
    'jobTitles' => [],
])

@php
    /**
     * منشأة and فرد ask different questions. On a new record both fieldsets are rendered and
     * the JS hides whichever the نوع الدلال select is not on; with no JS both stay visible and
     * both submit, because the server is the authority — the request clears the branch that
     * was not chosen and applies `required_if` to the one that was. On an existing record
     * only its own branch is offered, since switching would strand the other one's fields.
     */
    $isEdit = $broker !== null;
    $formKey = $isEdit ? 'broker-'.$broker->id : 'broker-new';
    $idPrefix = $formKey;
    $values = $broker ?? [];

    $valueOf = fn (string $field): mixed => old($field, data_get($values, $field));
    $errorFor = fn (string $field): ?string => $errors->first($field);

    $entityType = (string) $valueOf('entity_type') ?: \App\Models\FishMarketBroker::TYPE_ESTABLISHMENT;
    $isActive = $errors->any()
        ? (bool) old('is_active')
        : (bool) (data_get($values, 'is_active') ?? true);

    /** The rows already on the record, or the one blank row a new broker starts with. */
    $employeeRows = old('employees', $isEdit
        ? $broker->employees->map(fn (\App\Models\FishMarketBrokerEmployee $employee): array => [
            'job_title' => $employee->job_title,
            'nationality' => $employee->nationality,
            'headcount' => $employee->headcount,
        ])->all()
        : []);

    $employeeRows = $employeeRows ?: [['job_title' => '', 'nationality' => '', 'headcount' => '']];
@endphp

<form class="info-admin-form" method="post" data-broker-form
      action="{{ $isEdit ? route('information.admin.brokers.update', $broker) : route('information.admin.brokers.store') }}">
    @csrf
    @if ($isEdit)
        @method('PATCH')
    @endif

    <input type="hidden" name="form_key" value="{{ $formKey }}">

    <fieldset class="info-admin-fieldset">
        <legend>بيانات الدلال</legend>

        <div class="info-field-grid info-field-grid-three">
            @php($error = $errorFor('fish_market_id'))
            <div class="info-field">
                <label for="{{ $idPrefix }}_market">السوق <b aria-hidden="true">*</b></label>
                <select id="{{ $idPrefix }}_market" name="fish_market_id" required data-broker-market
                        @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_market_error" @endif>
                    <option value="">اختر السوق</option>
                    @foreach ($markets as $market)
                        <option value="{{ $market->id }}" @selected((string) $valueOf('fish_market_id') === (string) $market->id)>
                            {{ $market->governorate->name }} — {{ $market->name }}{{ $market->is_active ? '' : ' (متوقف)' }}
                        </option>
                    @endforeach
                </select>
                @if ($error)<small id="{{ $idPrefix }}_market_error" class="info-field-error">{{ $error }}</small>@endif
            </div>

            @php($error = $errorFor('entity_type'))
            <div class="info-field">
                <label for="{{ $idPrefix }}_entity_type">نوع الدلال <b aria-hidden="true">*</b></label>
                @if ($isEdit)
                    <input id="{{ $idPrefix }}_entity_type" value="{{ $broker->entityTypeLabel() }}" readonly>
                @else
                    <select id="{{ $idPrefix }}_entity_type" name="entity_type" required data-broker-entity-type
                            @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_entity_type_error" @endif>
                        @foreach (\App\Models\FishMarketBroker::ENTITY_TYPE_LABELS as $value => $label)
                            <option value="{{ $value }}" @selected($entityType === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                @endif
                @if ($error)<small id="{{ $idPrefix }}_entity_type_error" class="info-field-error">{{ $error }}</small>@endif
            </div>

            @php($error = $errorFor('phone'))
            <div class="info-field">
                <label for="{{ $idPrefix }}_phone">رقم الجوال / التليفون</label>
                <input id="{{ $idPrefix }}_phone" name="phone" value="{{ $valueOf('phone') }}"
                       inputmode="tel" maxlength="15" dir="ltr" placeholder="05xxxxxxxx"
                       @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_phone_error" @endif>
                @if ($error)<small id="{{ $idPrefix }}_phone_error" class="info-field-error">{{ $error }}</small>@endif
            </div>

            @php($error = $errorFor('fish_market_unit_id'))
            <div class="info-field">
                <label for="{{ $idPrefix }}_unit">الدكة المنسوب إليها</label>
                <select id="{{ $idPrefix }}_unit" name="fish_market_unit_id" data-broker-stall
                        @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_unit_error" @endif>
                    <option value="">اختر الدكة</option>
                    @foreach ($stalls as $stall)
                        <option value="{{ $stall->id }}" data-market="{{ $stall->fish_market_id }}"
                                @selected((string) $valueOf('fish_market_unit_id') === (string) $stall->id)>
                            {{ $stall->label ?? 'دكة حراج' }}@if ($stall->entity_name) — {{ $stall->entity_name }}@endif
                        </option>
                    @endforeach
                </select>
                @if ($error)<small id="{{ $idPrefix }}_unit_error" class="info-field-error">{{ $error }}</small>@endif
            </div>

            @php($error = $errorFor('stall_number'))
            <div class="info-field">
                <label for="{{ $idPrefix }}_stall_number">رقم الدكة</label>
                <input id="{{ $idPrefix }}_stall_number" name="stall_number" value="{{ $valueOf('stall_number') }}"
                       maxlength="20" dir="ltr" placeholder="14"
                       @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_stall_number_error" @endif>
                @if ($error)<small id="{{ $idPrefix }}_stall_number_error" class="info-field-error">{{ $error }}</small>@endif
            </div>
        </div>
    </fieldset>

    @if (! $isEdit || $entityType === \App\Models\FishMarketBroker::TYPE_INDIVIDUAL)
        <fieldset class="info-admin-fieldset" data-broker-branch="{{ \App\Models\FishMarketBroker::TYPE_INDIVIDUAL }}">
            <legend>بيانات الفرد</legend>

            <div class="info-field-grid info-field-grid-three">
                @php($error = $errorFor('full_name'))
                <div class="info-field info-field-span-two">
                    <label for="{{ $idPrefix }}_full_name">الاسم <b aria-hidden="true">*</b></label>
                    <input id="{{ $idPrefix }}_full_name" name="full_name" value="{{ $valueOf('full_name') }}"
                           minlength="3" maxlength="150" placeholder="الاسم كما يظهر في الهوية"
                           @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_full_name_error" @endif>
                    @if ($error)<small id="{{ $idPrefix }}_full_name_error" class="info-field-error">{{ $error }}</small>@endif
                </div>

                @php($error = $errorFor('nationality'))
                <div class="info-field">
                    <label for="{{ $idPrefix }}_nationality">الجنسية <b aria-hidden="true">*</b></label>
                    <select id="{{ $idPrefix }}_nationality" name="nationality"
                            @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_nationality_error" @endif>
                        <option value="">اختر الجنسية</option>
                        @foreach ($nationalities as $code => $label)
                            <option value="{{ $code }}" @selected((string) $valueOf('nationality') === (string) $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($error)<small id="{{ $idPrefix }}_nationality_error" class="info-field-error">{{ $error }}</small>@endif
                </div>

                @php($error = $errorFor('job_title'))
                <div class="info-field">
                    <label for="{{ $idPrefix }}_job_title">الوظيفة <b aria-hidden="true">*</b></label>
                    <select id="{{ $idPrefix }}_job_title" name="job_title"
                            @if ($error) aria-invalid="true" aria-describedby="{{ $idPrefix }}_job_title_error" @endif>
                        <option value="">اختر الوظيفة</option>
                        @foreach ($jobTitles as $code => $label)
                            <option value="{{ $code }}" @selected((string) $valueOf('job_title') === (string) $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($error)<small id="{{ $idPrefix }}_job_title_error" class="info-field-error">{{ $error }}</small>@endif
                </div>
            </div>
        </fieldset>
    @endif

    @if (! $isEdit || $entityType === \App\Models\FishMarketBroker::TYPE_ESTABLISHMENT)
        <fieldset class="info-admin-fieldset" data-broker-branch="{{ \App\Models\FishMarketBroker::TYPE_ESTABLISHMENT }}">
            <legend>بيانات المنشأة</legend>

            <div class="info-field-grid info-field-grid-three">
                <x-information.commercial-entity
                    :id-prefix="$idPrefix"
                    :values="$values"
                    :with-email="true"
                    :with-phone="false"
                    :required="false"
                />
            </div>
        </fieldset>
    @endif

    {{--
        الموظفون are counted, not named: one row per وظيفة and جنسية. The rows in the markup
        submit with no script, so the desk can still file one row per save; the script only
        adds and drops rows on the page.
    --}}
    <div class="info-admin-subhead">
        <h3>الموظفون</h3>

        <button type="button" class="info-add-button" data-broker-add-employee>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
            <span>إضافة موظف</span>
        </button>
    </div>

    <div class="info-repeat-list" data-broker-employee-list>
        @foreach ($employeeRows as $index => $employee)
            <x-information.broker-employee
                :index="$index" :employee="$employee" :id-prefix="$idPrefix"
                :job-titles="$jobTitles" :nationalities="$nationalities"
            />
        @endforeach
    </div>

    @error('employees')<small class="info-field-error info-collection-error">{{ $message }}</small>@enderror

    <template data-broker-employee-template>
        <x-information.broker-employee
            index="__INDEX__" :id-prefix="$idPrefix"
            :job-titles="$jobTitles" :nationalities="$nationalities"
        />
    </template>

    <div class="info-admin-form-actions">
        <label class="info-lookup-check">
            <input type="checkbox" name="is_active" value="1" @checked($isActive)>
            <span>دلال نشط</span>
        </label>

        <button class="info-button info-button-primary" type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg>
            <span>{{ $isEdit ? 'حفظ التعديلات' : 'إضافة الدلال' }}</span>
        </button>
    </div>
</form>
