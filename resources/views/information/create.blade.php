@extends('layouts.information')

@section('title', 'تسجيل بيانات القارب')

@section('topbar-actions')
    <a class="info-button info-button-secondary" href="{{ route('information.status.index') }}">متابعة طلباتي</a>

    <form method="post" action="{{ route('information.identity.destroy') }}">
        @csrf
        <button class="info-button info-button-secondary" type="submit">إنهاء الجلسة</button>
    </form>
@endsection

@section('content')
@php
    $workflowSteps = [
        'بيانات المالك',
        'بيانات القارب',
        'القبطان والبحارة',
        'أدوات الصيد',
        'المستندات',
        'المراجعة',
    ];

    $stepPrefixes = [
        1 => ['owner_', 'license_'],
        2 => ['port_id', 'boat_', 'registration_no', 'hull_', 'engine_', 'call_sign', 'berth_number', 'mooring_number'],
        3 => ['captain_', 'crew_'],
        4 => ['fishing_'],
        5 => ['documents'],
        6 => ['consent'],
    ];

    $fieldLabels = [
        'owner_full_name' => 'الاسم الرباعي للمالك',
        'owner_national_id' => 'رقم هوية المالك',
        'owner_nationality' => 'جنسية المالك',
        'owner_birth_date' => 'تاريخ ميلاد المالك',
        'owner_email' => 'البريد الإلكتروني للمالك',
        'owner_phone' => 'رقم جوال المالك',
        'owner_region' => 'منطقة المالك',
        'owner_governorate' => 'محافظة المالك',
        'owner_address' => 'العنوان التفصيلي',
        'license_number' => 'رقم رخصة الصيد',
        'license_issue_date' => 'تاريخ إصدار رخصة الصيد',
        'license_expiry_date' => 'تاريخ انتهاء رخصة الصيد',
        'port_id' => 'الميناء الأساسي',
        'boat_name' => 'اسم القارب بالعربية',
        'boat_name_en' => 'اسم القارب بالإنجليزية',
        'registration_no' => 'رقم القارب',
        'boat_type' => 'نوع القارب',
        'boat_classification' => 'تصنيف القارب',
        'hull_material' => 'مادة الهيكل',
        'boat_build_date' => 'تاريخ بناء القارب',
        'boat_license_expiry_date' => 'تاريخ انتهاء رخصة القارب',
        'hull_number' => 'رقم الهيكل',
        'engine_number' => 'رقم المحرك',
        'engine_serial_number' => 'الرقم التسلسلي للمحرك',
        'call_sign' => 'إشارة النداء الدولية',
        'berth_number' => 'رقم الرصيف',
        'mooring_number' => 'رقم الموقف',
        'captain_full_name' => 'اسم القبطان',
        'captain_national_id' => 'هوية القبطان',
        'captain_phone' => 'جوال القبطان',
        'captain_license_number' => 'رخصة القيادة البحرية',
        'captain_license_expiry_date' => 'انتهاء رخصة القيادة',
        'captain_fishing_license_number' => 'رقم رخصة صيد القبطان',
        'captain_fishing_license_issue_date' => 'تاريخ إصدار رخصة صيد القبطان',
        'captain_fishing_license_expiry_date' => 'تاريخ انتهاء رخصة صيد القبطان',
        'captain_nationality' => 'جنسية القبطان',
        'captain_photo' => 'صورة القبطان',
        'crew_members' => 'قائمة البحارة',
        'fishing_method' => 'أسلوب الصيد الرئيسي',
        'fishing_tools' => 'أدوات الصيد',
        'documents' => 'المستندات والمرفقات',
        'consent' => 'إقرار صحة البيانات',
    ];

    $documentTypes = config('information.document_types');
    $requiredDocumentCount = collect($documentTypes)->where('required', true)->count();
    $nationalities = \App\Models\Nationality::options();
    $crewRoles = \App\Models\CrewRole::options();
    $fishingToolTypes = \App\Models\FishingToolType::options();
    $fishingToolMaterials = \App\Models\FishingToolMaterial::options();
    $fishingToolConditions = \App\Models\FishingToolCondition::options();

    $crewMembers = old('crew_members');
    if (! is_array($crewMembers) || $crewMembers === []) {
        $crewMembers = [[
            'full_name' => '',
            'identity_number' => '',
            'phone' => '',
            'nationality' => 'saudi',
            'role' => 'fisher',
            'fishing_license_number' => '',
            'fishing_license_issue_date' => '',
            'fishing_license_expiry_date' => '',
        ]];
    }

    $fishingTools = old('fishing_tools');
    if (! is_array($fishingTools) || $fishingTools === []) {
        $fishingTools = [[
            'type' => '',
            'quantity' => 1,
            'size' => '',
            'material' => 'nylon',
            'condition' => 'serviceable',
            'is_primary' => true,
        ]];
    }

    $errorTargetId = static function (string $field): string {
        if (preg_match('/^crew_members\.(\d+)\.([^.]+)$/', $field, $matches) === 1) {
            return "crew_{$matches[1]}_{$matches[2]}";
        }

        if (preg_match('/^crew_photos\.(\d+)$/', $field, $matches) === 1) {
            return "crew_{$matches[1]}_photo";
        }

        if (preg_match('/^fishing_tools\.(\d+)\.([^.]+)$/', $field, $matches) === 1) {
            return "tool_{$matches[1]}_{$matches[2]}";
        }

        if (str_starts_with($field, 'documents.')) {
            return 'document_'.str_replace('.', '_', substr($field, 10));
        }

        return match ($field) {
            'crew_members' => 'crew_list',
            'fishing_tools' => 'fishing_tools_list',
            'documents' => 'documents_grid',
            default => str_replace('.', '_', $field),
        };
    };

    $errorLabel = static function (string $field) use ($fieldLabels, $documentTypes): string {
        if (preg_match('/^crew_members\.(\d+)\./', $field, $matches) === 1) {
            return 'بيانات البحار رقم '.((int) $matches[1] + 1);
        }

        if (preg_match('/^crew_photos\.(\d+)$/', $field, $matches) === 1) {
            return 'صورة البحار رقم '.((int) $matches[1] + 1);
        }

        if (preg_match('/^fishing_tools\.(\d+)\./', $field, $matches) === 1) {
            return 'أداة الصيد رقم '.((int) $matches[1] + 1);
        }

        if (str_starts_with($field, 'documents.')) {
            return data_get($documentTypes, substr($field, 10).'.label', 'أحد المستندات');
        }

        return $fieldLabels[$field] ?? 'حقل مطلوب';
    };

    $startStep = (int) old('current_step', 1);

    foreach ($errors->keys() as $field) {
        foreach ($stepPrefixes as $step => $prefixes) {
            foreach ($prefixes as $prefix) {
                if ($field === $prefix || str_starts_with($field, $prefix)) {
                    $startStep = $step;
                    break 3;
                }
            }
        }
    }
@endphp

<section class="info-page" aria-labelledby="portal-title">
    <header class="info-page-intro">
        <div class="info-page-intro-copy">
            <p class="info-eyebrow"><span></span>سجل بحري متكامل</p>
            <h1 id="portal-title">تسجيل بيانات القارب</h1>
        </div>

        <div class="info-session-status">
            <span class="info-session-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M7 11V8a5 5 0 0 1 10 0v3m-9 0h8a2 2 0 0 1 2 2v7H6v-7a2 2 0 0 1 2-2Z"></path></svg>
            </span>
            <span><small>جلسة إدخال آمنة</small><strong>جميع الحقول المعلّمة مطلوبة</strong></span>
        </div>
    </header>

    @if($errors->any())
        <div class="info-error-summary" role="alert" tabindex="-1" data-info-error-summary>
            <span class="info-error-summary-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M12 8v5m0 3h.01M10.3 4.6 2.8 18a1 1 0 0 0 .9 1.5h16.6a1 1 0 0 0 .9-1.5L13.7 4.6a1 1 0 0 0-1.7 0Z"></path></svg>
            </span>
            <div>
                <strong>تحتاج بعض البيانات إلى المراجعة</strong>
                <p>انتقل مباشرة إلى الحقل وعدّل القيمة، ثم أكمل الإرسال.</p>
                <ul>
                    @foreach($errors->getMessages() as $field => $messages)
                        @continue($field === 'website')
                        @foreach($messages as $message)
                            <li><a href="#{{ $errorTargetId($field) }}" data-info-error-target="{{ $errorTargetId($field) }}">{{ $errorLabel($field) }}: {{ $message }}</a></li>
                        @endforeach
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <form
        method="post"
        action="{{ route('information.store') }}"
        enctype="multipart/form-data"
        class="info-form-card"
        data-info-form
        data-start-step="{{ $startStep }}"
        data-has-unsaved-input="{{ $errors->any() ? '1' : '0' }}"
    >
        @csrf
        <input class="hidden" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="hidden" name="current_step" value="{{ $startStep }}" data-info-current-step>

        <div class="info-workflow-header">
            <div class="info-progress-copy" aria-live="polite">
                <span><b data-info-progress-step>الخطوة 1 من 6</b></span>
                <strong data-info-progress-title>بيانات المالك</strong>
            </div>
            <div class="info-progress-track" aria-hidden="true"><span data-info-progress-bar></span></div>

            <nav class="info-stepper" aria-label="خطوات تسجيل بيانات القارب">
                <ol>
                    @foreach($workflowSteps as $index => $step)
                        <li class="info-step" data-info-step="{{ $index + 1 }}">
                            <button type="button" data-info-step-target="{{ $index + 1 }}" aria-label="الخطوة {{ $index + 1 }}: {{ $step }}">
                                <span class="info-step-dot"><span>{{ $index + 1 }}</span><svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 10 3 3 7-7"></path></svg></span>
                                <span class="info-step-label"><strong>{{ $step }}</strong></span>
                            </button>
                        </li>
                    @endforeach
                </ol>
            </nav>
        </div>

        <div class="info-form-content">
            <section class="info-panel" data-info-panel="1">
                <div class="info-panel-layout">
                    <div class="info-panel-main">
                        <header class="info-panel-heading">
                            <span class="info-panel-index" aria-hidden="true">01</span>
                            <div><h2 tabindex="-1">بيانات المالك</h2></div>
                        </header>

                        <div class="info-field-grid info-field-grid-three">
                                <div class="info-field">
                                    <label for="owner_full_name">الاسم الرباعي <b aria-hidden="true">*</b></label>
                                    <input id="owner_full_name" name="owner_full_name" value="{{ old('owner_full_name') }}" required minlength="3" maxlength="150" autocomplete="name" placeholder="الاسم كما يظهر في الهوية" @error('owner_full_name') aria-invalid="true" aria-describedby="owner_full_name_error" @enderror>
                                    @error('owner_full_name')<small id="owner_full_name_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="owner_national_id">رقم الهوية / الإقامة <b aria-hidden="true">*</b></label>
                                    <input id="owner_national_id" name="owner_national_id" value="{{ $identity['national_id'] }}" required inputmode="numeric" pattern="[12١٢][0-9٠-٩۰-۹]{9}" maxlength="10" dir="ltr" readonly aria-readonly="true" @error('owner_national_id') aria-invalid="true" aria-describedby="owner_national_id_error" @enderror>
                                    <small class="info-field-hint">مأخوذ من بيانات الدخول ولا يمكن تعديله.</small>
                                    @error('owner_national_id')<small id="owner_national_id_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="owner_nationality">الجنسية <b aria-hidden="true">*</b></label>
                                    <select id="owner_nationality" name="owner_nationality" required @error('owner_nationality') aria-invalid="true" aria-describedby="owner_nationality_error" @enderror>
                                        <option value="">اختر الجنسية</option>
                                        @foreach($nationalities as $value => $label)
                                            <option value="{{ $value }}" @selected(old('owner_nationality', 'saudi') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('owner_nationality')<small id="owner_nationality_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="owner_birth_date">تاريخ الميلاد <b aria-hidden="true">*</b></label>
                                    <input type="date" id="owner_birth_date" name="owner_birth_date" value="{{ old('owner_birth_date') }}" required max="{{ today()->format('Y-m-d') }}" dir="ltr" @error('owner_birth_date') aria-invalid="true" aria-describedby="owner_birth_date_error" @enderror>
                                    @error('owner_birth_date')<small id="owner_birth_date_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="owner_email">البريد الإلكتروني</label>
                                    <input type="email" id="owner_email" name="owner_email" value="{{ old('owner_email') }}" maxlength="190" autocomplete="email" dir="ltr" placeholder="name@example.com" @error('owner_email') aria-invalid="true" aria-describedby="owner_email_error" @enderror>
                                    @error('owner_email')<small id="owner_email_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="owner_phone">رقم الجوال <b aria-hidden="true">*</b></label>
                                    <input id="owner_phone" name="owner_phone" value="{{ $identity['phone'] }}" required inputmode="tel" maxlength="15" autocomplete="tel" dir="ltr" readonly aria-readonly="true" @error('owner_phone') aria-invalid="true" aria-describedby="owner_phone_error" @enderror>
                                    <small class="info-field-hint">مأخوذ من بيانات الدخول ولا يمكن تعديله.</small>
                                    @error('owner_phone')<small id="owner_phone_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="license_number">رقم رخصة الصيد <b aria-hidden="true">*</b></label>
                                    <input id="license_number" name="license_number" value="{{ old('license_number') }}" required minlength="2" maxlength="80" dir="ltr" placeholder="LIC-2026-77" @error('license_number') aria-invalid="true" aria-describedby="license_number_error" @enderror>
                                    @error('license_number')<small id="license_number_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="license_issue_date">تاريخ إصدار الرخصة <b aria-hidden="true">*</b></label>
                                    <input type="date" id="license_issue_date" name="license_issue_date" value="{{ old('license_issue_date') }}" required max="{{ today()->format('Y-m-d') }}" dir="ltr" @error('license_issue_date') aria-invalid="true" aria-describedby="license_issue_date_error" @enderror>
                                    @error('license_issue_date')<small id="license_issue_date_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="license_expiry_date">تاريخ انتهاء الرخصة <b aria-hidden="true">*</b></label>
                                    <input type="date" id="license_expiry_date" name="license_expiry_date" value="{{ old('license_expiry_date') }}" required dir="ltr" @error('license_expiry_date') aria-invalid="true" aria-describedby="license_expiry_date_error" @enderror>
                                    @error('license_expiry_date')<small id="license_expiry_date_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="owner_region">المنطقة <b aria-hidden="true">*</b></label>
                                    <select id="owner_region" name="owner_region" required data-info-region @error('owner_region') aria-invalid="true" aria-describedby="owner_region_error" @enderror>
                                        <option value="">اختر المنطقة</option>
                                        @foreach($regions as $region)
                                            <option value="{{ $region->name }}" @selected(old('owner_region') === $region->name)>{{ $region->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('owner_region')<small id="owner_region_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="owner_governorate">المحافظة <b aria-hidden="true">*</b></label>
                                    <select id="owner_governorate" name="owner_governorate" required data-info-governorate @error('owner_governorate') aria-invalid="true" aria-describedby="owner_governorate_error" @enderror>
                                        <option value="">اختر المحافظة</option>
                                        @foreach($regions as $region)
                                            @foreach($region->governorates as $governorate)
                                                <option value="{{ $governorate->name }}" data-region="{{ $region->name }}" @selected(old('owner_governorate') === $governorate->name)>{{ $governorate->name }}</option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    @error('owner_governorate')<small id="owner_governorate_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field info-field-wide">
                                    <label for="owner_address">العنوان التفصيلي</label>
                                    <input id="owner_address" name="owner_address" value="{{ old('owner_address') }}" maxlength="250" autocomplete="street-address" placeholder="الحي، الشارع، وأقرب معلم" @error('owner_address') aria-invalid="true" aria-describedby="owner_address_error" @enderror>
                                    @error('owner_address')<small id="owner_address_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>
                        </div>
                    </div>

                    <aside class="info-guidance">
                        <span class="info-guidance-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0m8-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"></path></svg></span>
                        <p class="info-guidance-kicker">اكتمال ملف المالك</p>
                        <h3>طابق البيانات مع الوثائق</h3>
                        <p>هذه البيانات تُستخدم لإسناد القارب إلى مالكه وربط رخصة الصيد بالسجل الصحيح.</p>
                        <ul><li>الاسم مطابق للهوية</li><li>الرخصة سارية وتواريخها دقيقة</li><li>العنوان يشمل المنطقة والمحافظة</li></ul>
                    </aside>
                </div>

                <div class="info-actions info-actions-first">
                    <button class="info-button info-button-primary" type="button" data-info-next><span>التالي: بيانات القارب</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg></button>
                </div>
            </section>

            <section class="info-panel" data-info-panel="2">
                <div class="info-panel-layout">
                    <div class="info-panel-main">
                        <header class="info-panel-heading">
                            <span class="info-panel-index" aria-hidden="true">02</span>
                            <div><h2 tabindex="-1">بيانات القارب</h2></div>
                        </header>

                        <div class="info-field-grid info-field-grid-three">
                                <div class="info-field">
                                    <label for="boat_name">اسم القارب بالعربية <b aria-hidden="true">*</b></label>
                                    <input id="boat_name" name="boat_name" value="{{ old('boat_name') }}" required minlength="2" maxlength="150" placeholder="مثال: الشاكر" data-info-live-source="boat_name" @error('boat_name') aria-invalid="true" aria-describedby="boat_name_error" @enderror>
                                    @error('boat_name')<small id="boat_name_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="boat_name_en">اسم القارب بالإنجليزية <b aria-hidden="true">*</b></label>
                                    <input id="boat_name_en" name="boat_name_en" value="{{ old('boat_name_en') }}" required minlength="2" maxlength="150" dir="ltr" placeholder="Al-Shaker" @error('boat_name_en') aria-invalid="true" aria-describedby="boat_name_en_error" @enderror>
                                    @error('boat_name_en')<small id="boat_name_en_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="registration_no">رقم القارب <b aria-hidden="true">*</b></label>
                                    <input id="registration_no" name="registration_no" value="{{ old('registration_no') }}" required minlength="2" maxlength="50" dir="ltr" placeholder="66799" data-info-live-source="registration_no" @error('registration_no') aria-invalid="true" aria-describedby="registration_no_error" @enderror>
                                    @error('registration_no')<small id="registration_no_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="port_id">الميناء الأساسي <b aria-hidden="true">*</b></label>
                                    <select id="port_id" name="port_id" required @error('port_id') aria-invalid="true" aria-describedby="port_id_error" @enderror>
                                        <option value="">اختر الميناء</option>
                                        @foreach($ports as $port)
                                            <option value="{{ $port->id }}" @selected((string) old('port_id') === (string) $port->id)>{{ $port->name }} — {{ $port->governorate->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('port_id')<small id="port_id_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="boat_classification">تصنيف القارب <b aria-hidden="true">*</b></label>
                                    <select id="boat_classification" name="boat_classification" required @error('boat_classification') aria-invalid="true" aria-describedby="boat_classification_error" @enderror>
                                        <option value="">اختر التصنيف</option>
                                        @foreach(\App\Models\BoatClassification::options() as $value => $label)
                                            <option value="{{ $value }}" @selected(old('boat_classification') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('boat_classification')<small id="boat_classification_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="boat_type">نوع القارب <b aria-hidden="true">*</b></label>
                                    <select id="boat_type" name="boat_type" required @error('boat_type') aria-invalid="true" aria-describedby="boat_type_error" @enderror>
                                        <option value="">اختر النوع</option>
                                        @foreach(\App\Models\BoatType::options() as $value => $label)
                                            <option value="{{ $value }}" @selected(old('boat_type') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('boat_type')<small id="boat_type_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="hull_material">مادة الهيكل <b aria-hidden="true">*</b></label>
                                    <select id="hull_material" name="hull_material" required @error('hull_material') aria-invalid="true" aria-describedby="hull_material_error" @enderror>
                                        <option value="">اختر المادة</option>
                                        @foreach(\App\Models\HullMaterial::options() as $value => $label)
                                            <option value="{{ $value }}" @selected(old('hull_material') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('hull_material')<small id="hull_material_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="boat_build_date">تاريخ بناء القارب <b aria-hidden="true">*</b></label>
                                    <input type="date" id="boat_build_date" name="boat_build_date" value="{{ old('boat_build_date') }}" required max="{{ today()->format('Y-m-d') }}" dir="ltr" @error('boat_build_date') aria-invalid="true" aria-describedby="boat_build_date_error" @enderror>
                                    @error('boat_build_date')<small id="boat_build_date_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="boat_license_expiry_date">تاريخ انتهاء رخصة القارب <b aria-hidden="true">*</b></label>
                                    <input type="date" id="boat_license_expiry_date" name="boat_license_expiry_date" value="{{ old('boat_license_expiry_date') }}" required dir="ltr" @error('boat_license_expiry_date') aria-invalid="true" aria-describedby="boat_license_expiry_date_error" @enderror>
                                    @error('boat_license_expiry_date')<small id="boat_license_expiry_date_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="hull_number">رقم الهيكل <b aria-hidden="true">*</b></label>
                                    <input id="hull_number" name="hull_number" value="{{ old('hull_number') }}" required maxlength="80" dir="ltr" placeholder="HWT-66799-2024" @error('hull_number') aria-invalid="true" aria-describedby="hull_number_error" @enderror>
                                    @error('hull_number')<small id="hull_number_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="engine_number">رقم المحرك <b aria-hidden="true">*</b></label>
                                    <input id="engine_number" name="engine_number" value="{{ old('engine_number') }}" required maxlength="80" dir="ltr" placeholder="ENG-66799" @error('engine_number') aria-invalid="true" aria-describedby="engine_number_error" @enderror>
                                    @error('engine_number')<small id="engine_number_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="engine_serial_number">الرقم التسلسلي للمحرك <b aria-hidden="true">*</b></label>
                                    <input id="engine_serial_number" name="engine_serial_number" value="{{ old('engine_serial_number') }}" required maxlength="80" dir="ltr" placeholder="SER-66799" @error('engine_serial_number') aria-invalid="true" aria-describedby="engine_serial_number_error" @enderror>
                                    @error('engine_serial_number')<small id="engine_serial_number_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="call_sign">إشارة النداء الدولية (Call Sign)</label>
                                    <input id="call_sign" name="call_sign" value="{{ old('call_sign') }}" maxlength="30" dir="ltr" placeholder="HZ-66799" @error('call_sign') aria-invalid="true" aria-describedby="call_sign_error" @enderror>
                                    @error('call_sign')<small id="call_sign_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="berth_number">رقم الرصيف</label>
                                    <input id="berth_number" name="berth_number" value="{{ old('berth_number') }}" maxlength="30" dir="ltr" placeholder="مثال: 12" @error('berth_number') aria-invalid="true" aria-describedby="berth_number_error" @enderror>
                                    @error('berth_number')<small id="berth_number_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="mooring_number">رقم الموقف</label>
                                    <input id="mooring_number" name="mooring_number" value="{{ old('mooring_number') }}" maxlength="30" dir="ltr" placeholder="مثال: B-07" @error('mooring_number') aria-invalid="true" aria-describedby="mooring_number_error" @enderror>
                                    @error('mooring_number')<small id="mooring_number_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>
                        </div>
                    </div>

                    <aside class="info-guidance info-vessel-card">
                        <span class="info-guidance-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 14 6.5 8h11L20 14m-16 0h16l-2 5H6l-2-5Zm5-6V5h6v3M2 21c2 0 2-1 4-1s2 1 4 1 2-1 4-1 2 1 4 1 2-1 4-1"></path></svg></span>
                        <p class="info-guidance-kicker">معاينة السجل</p>
                        <h3 data-info-live-target="boat_name">قارب بدون اسم</h3>
                        <strong data-info-live-target="registration_no" dir="ltr">—</strong>
                        <p>تأكد من مطابقة رقم القارب مع الاستمارة ورقم الهيكل المثبت على البدن.</p>
                        <span class="info-mini-badge"><i></i> بيانات هيكل ومحرك كاملة</span>
                    </aside>
                </div>

                <div class="info-actions">
                    <button class="info-button info-button-secondary" type="button" data-info-previous><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg><span>السابق</span></button>
                    <button class="info-button info-button-primary" type="button" data-info-next><span>التالي: القبطان والبحارة</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg></button>
                </div>
            </section>

            <section class="info-panel" data-info-panel="3">
                <div class="info-panel-layout">
                    <div class="info-panel-main">
                        <header class="info-panel-heading">
                            <span class="info-panel-index" aria-hidden="true">03</span>
                            <div><h2 tabindex="-1">القبطان والبحارة</h2></div>
                        </header>

                        <div class="info-field-grid info-field-grid-three">
                                <div class="info-field">
                                    <label for="captain_full_name">الاسم الرباعي <b aria-hidden="true">*</b></label>
                                    <input id="captain_full_name" name="captain_full_name" value="{{ old('captain_full_name') }}" required minlength="3" maxlength="150" autocomplete="name" placeholder="الاسم كما يظهر في الهوية" @error('captain_full_name') aria-invalid="true" aria-describedby="captain_full_name_error" @enderror>
                                    @error('captain_full_name')<small id="captain_full_name_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="captain_national_id">رقم الهوية / الإقامة <b aria-hidden="true">*</b></label>
                                    <input id="captain_national_id" name="captain_national_id" value="{{ old('captain_national_id') }}" required inputmode="numeric" pattern="[12١٢][0-9٠-٩۰-۹]{9}" maxlength="10" dir="ltr" placeholder="10 أرقام" @error('captain_national_id') aria-invalid="true" aria-describedby="captain_national_id_error" @enderror>
                                    @error('captain_national_id')<small id="captain_national_id_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="captain_phone">رقم الجوال <b aria-hidden="true">*</b></label>
                                    <input id="captain_phone" name="captain_phone" value="{{ old('captain_phone') }}" required inputmode="tel" maxlength="15" autocomplete="tel" dir="ltr" placeholder="05xxxxxxxx" @error('captain_phone') aria-invalid="true" aria-describedby="captain_phone_error" @enderror>
                                    @error('captain_phone')<small id="captain_phone_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="captain_nationality">الجنسية <b aria-hidden="true">*</b></label>
                                    <select id="captain_nationality" name="captain_nationality" required @error('captain_nationality') aria-invalid="true" aria-describedby="captain_nationality_error" @enderror>
                                        <option value="">اختر الجنسية</option>
                                        @foreach($nationalities as $value => $label)
                                            <option value="{{ $value }}" @selected(old('captain_nationality', 'saudi') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('captain_nationality')<small id="captain_nationality_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="captain_license_number">رخصة القيادة البحرية <b aria-hidden="true">*</b></label>
                                    <input id="captain_license_number" name="captain_license_number" value="{{ old('captain_license_number') }}" required maxlength="80" dir="ltr" placeholder="MAR-7788" @error('captain_license_number') aria-invalid="true" aria-describedby="captain_license_number_error" @enderror>
                                    @error('captain_license_number')<small id="captain_license_number_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="captain_license_expiry_date">انتهاء رخصة القيادة <b aria-hidden="true">*</b></label>
                                    <input type="date" id="captain_license_expiry_date" name="captain_license_expiry_date" value="{{ old('captain_license_expiry_date') }}" required dir="ltr" @error('captain_license_expiry_date') aria-invalid="true" aria-describedby="captain_license_expiry_date_error" @enderror>
                                    @error('captain_license_expiry_date')<small id="captain_license_expiry_date_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="captain_fishing_license_number">رقم رخصة الصيد <b aria-hidden="true">*</b></label>
                                    <input id="captain_fishing_license_number" name="captain_fishing_license_number" value="{{ old('captain_fishing_license_number') }}" required minlength="2" maxlength="80" dir="ltr" placeholder="LIC-2026-77" @error('captain_fishing_license_number') aria-invalid="true" aria-describedby="captain_fishing_license_number_error" @enderror>
                                    @error('captain_fishing_license_number')<small id="captain_fishing_license_number_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="captain_fishing_license_issue_date">تاريخ إصدار رخصة الصيد <b aria-hidden="true">*</b></label>
                                    <input type="date" id="captain_fishing_license_issue_date" name="captain_fishing_license_issue_date" value="{{ old('captain_fishing_license_issue_date') }}" required max="{{ today()->format('Y-m-d') }}" dir="ltr" @error('captain_fishing_license_issue_date') aria-invalid="true" aria-describedby="captain_fishing_license_issue_date_error" @enderror>
                                    @error('captain_fishing_license_issue_date')<small id="captain_fishing_license_issue_date_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>

                                <div class="info-field">
                                    <label for="captain_fishing_license_expiry_date">تاريخ انتهاء رخصة الصيد <b aria-hidden="true">*</b></label>
                                    <input type="date" id="captain_fishing_license_expiry_date" name="captain_fishing_license_expiry_date" value="{{ old('captain_fishing_license_expiry_date') }}" required dir="ltr" @error('captain_fishing_license_expiry_date') aria-invalid="true" aria-describedby="captain_fishing_license_expiry_date_error" @enderror>
                                    @error('captain_fishing_license_expiry_date')<small id="captain_fishing_license_expiry_date_error" class="info-field-error">{{ $message }}</small>@enderror
                                </div>
                        </div>

                        <div class="info-inline-upload">
                            <div><strong>صورة القبطان</strong><span>صورة شخصية حديثة وواضحة — اختيارية.</span></div>
                            <x-information.upload-card
                                id="captain_photo"
                                name="captain_photo"
                                error-name="captain_photo"
                                label="صورة القبطان"
                                description="JPG أو PNG"
                                :compact="true"
                                :image-only="true"
                            />
                        </div>

                        <div class="info-list-block" aria-labelledby="crew-title">
                            <header class="info-list-heading">
                                <h3 id="crew-title">قائمة البحارة</h3>
                                <button type="button" class="info-add-button" data-info-add-crew><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg> إضافة بحار</button>
                            </header>

                            <input type="hidden" id="crew_count" name="crew_count" value="{{ old('crew_count', count($crewMembers)) }}" data-info-crew-count>
                            <div id="crew_list" class="info-repeat-list" data-info-crew-list>
                                @foreach($crewMembers as $index => $crewMember)
                                    <x-information.crew-member :index="$index" :member="$crewMember" :nationalities="$nationalities" :roles="$crewRoles" />
                                @endforeach
                            </div>
                            @error('crew_members')<small id="crew_members_error" class="info-field-error info-collection-error">{{ $message }}</small>@enderror

                            <template data-info-crew-template>
                                <x-information.crew-member index="__INDEX__" :nationalities="$nationalities" :roles="$crewRoles" />
                            </template>
                        </div>
                    </div>

                    <aside class="info-guidance info-sticky-guidance">
                        <span class="info-guidance-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m7-10a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm13 10v-2a4 4 0 0 0-3-3.87m-2-12a4 4 0 0 1 0 7.75"></path></svg></span>
                        <p class="info-guidance-kicker">الطاقم المسجل</p>
                        <h3><span data-info-crew-total>{{ count($crewMembers) }}</span> بحّار</h3>
                        <p>العدد يُحدّث تلقائياً مع إضافة أو حذف السجلات، ويُحفظ كل عضو بكامل بياناته.</p>
                        <ul><li>هوية مختلفة عن هوية القبطان</li><li>رقم جوال متاح لكل بحار</li><li>الدور ورخصة الصيد موضحان</li></ul>
                    </aside>
                </div>

                <div class="info-actions">
                    <button class="info-button info-button-secondary" type="button" data-info-previous><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg><span>السابق</span></button>
                    <button class="info-button info-button-primary" type="button" data-info-next><span>التالي: أدوات الصيد</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg></button>
                </div>
            </section>

            <section class="info-panel" data-info-panel="4">
                <div class="info-panel-layout">
                    <div class="info-panel-main">
                        <header class="info-panel-heading">
                            <span class="info-panel-index" aria-hidden="true">04</span>
                            <div><h2 tabindex="-1">أدوات الصيد</h2></div>
                        </header>

                        <div class="info-field-grid info-field-grid-three">
                            <div class="info-field">
                                <label for="fishing_method">أسلوب الصيد <b aria-hidden="true">*</b></label>
                                <select id="fishing_method" name="fishing_method" required @error('fishing_method') aria-invalid="true" aria-describedby="fishing_method_error" @enderror>
                                    <option value="">اختر الأسلوب</option>
                                    @foreach(\App\Models\FishingMethod::options() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('fishing_method') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('fishing_method')<small id="fishing_method_error" class="info-field-error">{{ $message }}</small>@enderror
                            </div>
                        </div>

                        <div class="info-list-block" aria-labelledby="fishing-tools-title">
                            <header class="info-list-heading">
                                <h3 id="fishing-tools-title">سجل الأدوات</h3>
                                <button type="button" class="info-add-button" data-info-add-tool><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg> إضافة أداة</button>
                            </header>

                            <div id="fishing_tools_list" class="info-repeat-list" data-info-tool-list>
                                @foreach($fishingTools as $index => $fishingTool)
                                    <x-information.fishing-tool :index="$index" :tool="$fishingTool" :types="$fishingToolTypes" :materials="$fishingToolMaterials" :conditions="$fishingToolConditions" />
                                @endforeach
                            </div>
                            @error('fishing_tools')<small id="fishing_tools_error" class="info-field-error info-collection-error">{{ $message }}</small>@enderror

                            <template data-info-tool-template>
                                <x-information.fishing-tool index="__INDEX__" :types="$fishingToolTypes" :materials="$fishingToolMaterials" :conditions="$fishingToolConditions" />
                            </template>
                        </div>

                        <div class="info-metrics" aria-live="polite">
                            <div><span>إجمالي القطع</span><strong data-info-tools-total>0</strong></div>
                            <div><span>أنواع الأدوات</span><strong data-info-tools-types>0</strong></div>
                            <div><span>الأدوات الأساسية</span><strong data-info-tools-primary>0</strong></div>
                        </div>
                    </div>

                    <aside class="info-guidance info-sticky-guidance">
                        <span class="info-guidance-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 19c3-5 5-8 8-8 4 0 4 5 8 5M5 5v6m-3-3h6m8-5v6m-3-3h6"></path></svg></span>
                        <p class="info-guidance-kicker">جرد معدات الصيد</p>
                        <h3>أداة أساسية واحدة</h3>
                        <p>حدد الأداة الأكثر استخداماً كأساسية. يمنع النظام اختيار أكثر من أداة أساسية في السجل نفسه.</p>
                        <ul><li>الكمية رقم صحيح</li><li>الحالة تعكس صلاحية الاستخدام</li><li>المقاس اختياري عند عدم انطباقه</li></ul>
                    </aside>
                </div>

                <div class="info-actions">
                    <button class="info-button info-button-secondary" type="button" data-info-previous><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg><span>السابق</span></button>
                    <button class="info-button info-button-primary" type="button" data-info-next><span>التالي: المستندات</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg></button>
                </div>
            </section>

            <section class="info-panel" data-info-panel="5">
                <div class="info-panel-layout">
                    <div class="info-panel-main">
                        <header class="info-panel-heading">
                            <span class="info-panel-index" aria-hidden="true">05</span>
                            <div><h2 tabindex="-1">المستندات والمرفقات</h2></div>
                        </header>

                        <div class="info-upload-notice">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Zm0-6v-4m0-4h.01"></path></svg>
                            <p><strong>صيَغ الملفات المعتمدة</strong><span>صور المحرك والقارب بصيغة JPG أو PNG؛ وبقية المستندات PDF أو JPG أو PNG، بحد أقصى 10 ميجابايت لكل ملف.</span></p>
                        </div>

                        <div id="documents_grid" class="info-document-grid" data-info-document-grid>
                            @foreach($documentTypes as $key => $documentType)
                                <x-information.upload-card
                                    :id="'document_'.$key"
                                    :name="'documents['.$key.']'"
                                    :error-name="'documents.'.$key"
                                    :label="$documentType['label']"
                                    :description="$documentType['description']"
                                    :required="$documentType['required']"
                                    :image-only="$documentType['image_only'] ?? false"
                                />
                            @endforeach
                        </div>
                        @error('documents')<small id="documents_error" class="info-field-error info-collection-error">{{ $message }}</small>@enderror
                    </div>

                    <aside class="info-guidance info-document-guidance">
                        <span class="info-guidance-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M14 3v5h5M5 3h9l5 5v13H5V3Zm3 10h8m-8 4h6"></path></svg></span>
                        <p class="info-guidance-kicker">اكتمال المستندات</p>
                        <h3><span data-info-required-document-count>0</span> / {{ $requiredDocumentCount }} مطلوبة</h3>
                        <div class="info-document-progress" aria-hidden="true"><span data-info-document-progress></span></div>
                        <p>تم إرفاق <b data-info-document-count>0</b> من {{ count($documentTypes) }} ملفات. صورة المحرك وصورة القارب والاستمارة ورخصة القارب مطلوبة.</p>
                        <ul><li>الملف واضح وقابل للقراءة</li><li>لا توجد كلمات مرور على PDF</li><li>كل وثيقة في خانتها الصحيحة</li></ul>
                    </aside>
                </div>

                <div class="info-actions">
                    <button class="info-button info-button-secondary" type="button" data-info-previous><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg><span>السابق</span></button>
                    <button class="info-button info-button-primary" type="button" data-info-next><span>التالي: المراجعة</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg></button>
                </div>
            </section>

            <section class="info-panel" data-info-panel="6">
                <header class="info-panel-heading info-panel-heading-review">
                    <span class="info-panel-index" aria-hidden="true">06</span>
                    <div><h2 tabindex="-1">المراجعة النهائية</h2></div>
                </header>

                <div class="info-review-grid">
                    <article class="info-review-card">
                        <header><span><i>01</i><strong>بيانات المالك والرخصة</strong></span><button type="button" data-info-edit="1" aria-label="تعديل بيانات المالك والرخصة">تعديل</button></header>
                        <dl>
                            <div><dt>الاسم</dt><dd data-info-review="owner_full_name">—</dd></div>
                            <div><dt>رقم الهوية</dt><dd data-info-review="owner_national_id" data-info-mask="identity" dir="ltr">—</dd></div>
                            <div><dt>الجنسية</dt><dd data-info-review="owner_nationality">—</dd></div>
                            <div><dt>تاريخ الميلاد</dt><dd data-info-review="owner_birth_date" dir="ltr">—</dd></div>
                            <div><dt>البريد الإلكتروني</dt><dd data-info-review="owner_email" dir="ltr">—</dd></div>
                            <div><dt>رقم الجوال</dt><dd data-info-review="owner_phone" dir="ltr">—</dd></div>
                            <div><dt>رخصة الصيد</dt><dd data-info-review="license_number" dir="ltr">—</dd></div>
                            <div><dt>إصدار الرخصة</dt><dd data-info-review="license_issue_date" dir="ltr">—</dd></div>
                            <div><dt>انتهاء الرخصة</dt><dd data-info-review="license_expiry_date" dir="ltr">—</dd></div>
                            <div><dt>المنطقة</dt><dd data-info-review="owner_region">—</dd></div>
                            <div><dt>المحافظة</dt><dd data-info-review="owner_governorate">—</dd></div>
                            <div><dt>العنوان</dt><dd data-info-review="owner_address">—</dd></div>
                        </dl>
                    </article>

                    <article class="info-review-card">
                        <header><span><i>02</i><strong>بيانات القارب والمحرك</strong></span><button type="button" data-info-edit="2" aria-label="تعديل بيانات القارب والمحرك">تعديل</button></header>
                        <dl>
                            <div><dt>الاسم بالعربية</dt><dd data-info-review="boat_name">—</dd></div>
                            <div><dt>الاسم بالإنجليزية</dt><dd data-info-review="boat_name_en" dir="ltr">—</dd></div>
                            <div><dt>رقم القارب</dt><dd data-info-review="registration_no" dir="ltr">—</dd></div>
                            <div><dt>الميناء</dt><dd data-info-review="port_id">—</dd></div>
                            <div><dt>التصنيف</dt><dd data-info-review="boat_classification">—</dd></div>
                            <div><dt>نوع القارب</dt><dd data-info-review="boat_type">—</dd></div>
                            <div><dt>مادة الهيكل</dt><dd data-info-review="hull_material">—</dd></div>
                            <div><dt>تاريخ البناء</dt><dd data-info-review="boat_build_date" dir="ltr">—</dd></div>
                            <div><dt>انتهاء رخصة القارب</dt><dd data-info-review="boat_license_expiry_date" dir="ltr">—</dd></div>
                            <div><dt>رقم الهيكل</dt><dd data-info-review="hull_number" dir="ltr">—</dd></div>
                            <div><dt>رقم المحرك</dt><dd data-info-review="engine_number" dir="ltr">—</dd></div>
                            <div><dt>تسلسل المحرك</dt><dd data-info-review="engine_serial_number" dir="ltr">—</dd></div>
                            <div><dt>إشارة النداء</dt><dd data-info-review="call_sign" dir="ltr">—</dd></div>
                            <div><dt>رقم الرصيف</dt><dd data-info-review="berth_number" dir="ltr">—</dd></div>
                            <div><dt>رقم الموقف</dt><dd data-info-review="mooring_number" dir="ltr">—</dd></div>
                        </dl>
                    </article>

                    <article class="info-review-card">
                        <header><span><i>03</i><strong>القبطان والبحارة</strong></span><button type="button" data-info-edit="3" aria-label="تعديل بيانات القبطان والبحارة">تعديل</button></header>
                        <dl>
                            <div><dt>اسم القبطان</dt><dd data-info-review="captain_full_name">—</dd></div>
                            <div><dt>هوية القبطان</dt><dd data-info-review="captain_national_id" data-info-mask="identity" dir="ltr">—</dd></div>
                            <div><dt>جوال القبطان</dt><dd data-info-review="captain_phone" dir="ltr">—</dd></div>
                            <div><dt>رخصة القيادة</dt><dd data-info-review="captain_license_number" dir="ltr">—</dd></div>
                            <div><dt>انتهاء رخصة القيادة</dt><dd data-info-review="captain_license_expiry_date" dir="ltr">—</dd></div>
                            <div><dt>رخصة الصيد</dt><dd data-info-review="captain_fishing_license_number" dir="ltr">—</dd></div>
                            <div><dt>إصدار رخصة الصيد</dt><dd data-info-review="captain_fishing_license_issue_date" dir="ltr">—</dd></div>
                            <div><dt>انتهاء رخصة الصيد</dt><dd data-info-review="captain_fishing_license_expiry_date" dir="ltr">—</dd></div>
                            <div><dt>الجنسية</dt><dd data-info-review="captain_nationality">—</dd></div>
                            <div><dt>صورة القبطان</dt><dd data-info-review="captain_photo">غير مرفقة</dd></div>
                            <div><dt>عدد البحارة</dt><dd data-info-review-crew-count>{{ count($crewMembers) }}</dd></div>
                        </dl>
                    </article>

                    <article class="info-review-card">
                        <header><span><i>04</i><strong>أدوات الصيد</strong></span><button type="button" data-info-edit="4" aria-label="تعديل بيانات أدوات الصيد">تعديل</button></header>
                        <dl>
                            <div><dt>أسلوب الصيد</dt><dd data-info-review="fishing_method">—</dd></div>
                            <div><dt>أنواع الأدوات</dt><dd data-info-review-tool-types>0</dd></div>
                            <div><dt>إجمالي القطع</dt><dd data-info-review-tool-total>0</dd></div>
                            <div><dt>الأداة الأساسية</dt><dd data-info-review-primary-tool>—</dd></div>
                        </dl>
                    </article>

                    <article class="info-review-card info-review-card-wide">
                        <header><span><i>05</i><strong>المستندات والمرفقات</strong></span><button type="button" data-info-edit="5" aria-label="تعديل المستندات والمرفقات">تعديل</button></header>
                        <div class="info-review-documents">
                            <div><strong data-info-review-document-count>0 / {{ count($documentTypes) }}</strong><span>ملفات مرفوعة</span></div>
                            <ul data-info-review-document-list><li>لم تُحدد ملفات بعد</li></ul>
                        </div>
                    </article>
                </div>

                <label class="info-consent" for="consent">
                    <input id="consent" type="checkbox" name="consent" value="1" @checked(old('consent')) required @error('consent') aria-invalid="true" aria-describedby="consent_error" @enderror>
                    <span class="info-checkmark" aria-hidden="true"><svg viewBox="0 0 20 20"><path d="m5 10 3 3 7-7"></path></svg></span>
                    <span><strong>أقر بأن جميع البيانات والمرفقات صحيحة ومكتملة</strong><small>سيتم حفظ السجل والوثائق الخاصة وإصدار رقم مرجعي للمتابعة.</small></span>
                </label>
                @error('consent')<small id="consent_error" class="info-field-error info-consent-error">{{ $message }}</small>@enderror

                <div class="info-actions info-actions-submit">
                    <button class="info-button info-button-secondary" type="button" data-info-previous><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg><span>السابق</span></button>
                    <div>
                        <p><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 11V8a5 5 0 0 1 10 0v3m-9 0h8a2 2 0 0 1 2 2v7H6v-7a2 2 0 0 1 2-2Z"></path></svg> حفظ آمن ومرفقات خاصة</p>
                        <button class="info-button info-button-primary info-submit-button" type="submit" data-info-submit><span>حفظ وإرسال السجل</span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"></path></svg></button>
                    </div>
                </div>
            </section>

            <p class="sr-only" aria-live="polite" data-info-announcer></p>
        </div>
    </form>
</section>
@endsection
