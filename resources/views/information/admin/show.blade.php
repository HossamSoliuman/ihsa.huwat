@extends('layouts.information-admin')

@section('title', 'مراجعة الطلب '.$submission->reference_no)

@section('content')
@php
    $documentTypes = config('information.document_types');
    $nationalities = \App\Models\Nationality::labels();
    $boatTypes = \App\Models\BoatType::labels();
    $boatClassifications = \App\Models\BoatClassification::labels();
    $hullMaterials = \App\Models\HullMaterial::labels();
    $crewRoles = \App\Models\CrewRole::labels();
    $toolTypes = \App\Models\FishingToolType::labels();
    $toolMaterials = \App\Models\FishingToolMaterial::labels();
    $toolConditions = \App\Models\FishingToolCondition::labels();
    $fishingMethods = \App\Models\FishingMethod::labels();
    $boat = $submission->boat_data ?? [];
    $captain = $submission->captain_data ?? [];
    $crewMembers = $submission->crew_members ?? [];
    $fishingTools = $submission->fishing_tools ?? [];
    $documentPaths = $submission->document_paths ?? [];

    $statusActions = [
        'under_review' => ['label' => 'بدء المراجعة', 'tone' => 'sea'],
        'needs_edit' => ['label' => 'طلب تعديل', 'tone' => 'gold'],
        'approved' => ['label' => 'اعتماد الطلب', 'tone' => 'approve'],
        'rejected' => ['label' => 'رفض الطلب', 'tone' => 'danger'],
    ];
@endphp

<header class="info-admin-header info-admin-header-detail">
    <div>
        <a class="info-admin-back" href="{{ route('information.admin.index') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m14 6 6 6-6 6"></path><path d="M20 12H4"></path></svg>
            <span>العودة إلى قائمة الطلبات</span>
        </a>
        <h1>مراجعة الطلب <span class="info-admin-reference" dir="ltr">{{ $submission->reference_no }}</span></h1>
        <p class="info-admin-header-meta">
            <span>{{ $submission->owner_full_name }}</span>
            <span aria-hidden="true">·</span>
            <span>{{ $submission->port?->name ?? 'ميناء غير محدد' }}</span>
            <span aria-hidden="true">·</span>
            <time datetime="{{ $submission->submitted_at?->toIso8601String() }}">{{ $submission->submitted_at?->format('Y/m/d — H:i') }}</time>
        </p>
    </div>

    <x-information.status-chip :status="$submission->status" class="info-status-chip-lg" />
</header>

<div class="info-admin-detail">
    <section class="info-form-card info-admin-panel" data-info-tabs>
        <div class="info-admin-tablist" role="tablist" aria-label="أقسام الطلب">
            @php
                $tabs = [
                    'owner' => 'بيانات المالك',
                    'boat' => 'القارب',
                    'crew' => 'الكابتن والبحارة ('.(count($crewMembers) + 1).')',
                    'tools' => 'أدوات الصيد ('.count($fishingTools).')',
                    'documents' => 'المستندات ('.count($documentPaths).')',
                    'activity' => 'النشاط',
                ];
            @endphp

            @foreach ($tabs as $tabKey => $tabLabel)
                <button type="button" role="tab" id="tab-{{ $tabKey }}" class="info-admin-tab @if ($loop->first) is-active @endif"
                        aria-controls="panel-{{ $tabKey }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}" data-info-tab="{{ $tabKey }}">
                    {{ $tabLabel }}
                </button>
            @endforeach
        </div>

        <div class="info-admin-tabpanels">
            <div role="tabpanel" id="panel-owner" aria-labelledby="tab-owner" class="info-admin-tabpanel" data-info-tabpanel="owner">
                <h2>بيانات المالك</h2>
                <dl class="info-admin-datagrid">
                    <div><dt>الاسم الرباعي</dt><dd>{{ $submission->owner_full_name }}</dd></div>
                    <div><dt>رقم الهوية</dt><dd dir="ltr">{{ $submission->owner_national_id }}</dd></div>
                    <div><dt>الجنسية</dt><dd>{{ $nationalities[$submission->owner_nationality] ?? $submission->owner_nationality ?? '—' }}</dd></div>
                    <div><dt>تاريخ الميلاد</dt><dd>{{ $submission->owner_birth_date?->format('Y/m/d') ?? '—' }}</dd></div>
                    <div><dt>رقم الجوال</dt><dd dir="ltr">{{ $submission->owner_phone }}</dd></div>
                    <div><dt>البريد الإلكتروني</dt><dd dir="ltr">{{ $submission->owner_email ?? '—' }}</dd></div>
                    <div><dt>المحافظة</dt><dd>{{ $submission->owner_governorate ?? '—' }}</dd></div>
                    <div><dt>المنطقة</dt><dd>{{ $submission->owner_region ?? '—' }}</dd></div>
                    <div><dt>المدينة</dt><dd>{{ $submission->owner_city ?? '—' }}</dd></div>
                    <div class="info-field-wide"><dt>العنوان</dt><dd>{{ $submission->owner_address ?? '—' }}</dd></div>
                    <div><dt>رقم الرخصة</dt><dd dir="ltr">{{ $submission->license_number }}</dd></div>
                    <div><dt>إصدار الرخصة</dt><dd>{{ $submission->license_issue_date?->format('Y/m/d') ?? '—' }}</dd></div>
                    <div><dt>انتهاء الرخصة</dt><dd>{{ $submission->license_expiry_date?->format('Y/m/d') ?? '—' }}</dd></div>
                </dl>
            </div>

            <div role="tabpanel" id="panel-boat" aria-labelledby="tab-boat" class="info-admin-tabpanel" data-info-tabpanel="boat" hidden>
                <h2>بيانات القارب</h2>
                <dl class="info-admin-datagrid">
                    <div><dt>اسم القارب</dt><dd>{{ $boat['boat_name'] ?? $submission->boat?->name ?? '—' }}</dd></div>
                    <div><dt>الاسم بالإنجليزية</dt><dd dir="ltr">{{ $boat['boat_name_en'] ?? '—' }}</dd></div>
                    <div><dt>رقم التسجيل</dt><dd dir="ltr">{{ $boat['registration_no'] ?? $submission->boat?->registration_no ?? '—' }}</dd></div>
                    <div><dt>نوع القارب</dt><dd>{{ $boatTypes[$boat['boat_type'] ?? ''] ?? '—' }}</dd></div>
                    <div><dt>التصنيف</dt><dd>{{ $boatClassifications[$boat['boat_classification'] ?? ''] ?? '—' }}</dd></div>
                    <div><dt>مادة الهيكل</dt><dd>{{ $hullMaterials[$boat['hull_material'] ?? ''] ?? '—' }}</dd></div>
                    <div><dt>تاريخ البناء</dt><dd>{{ $boat['boat_build_date'] ?? '—' }}</dd></div>
                    <div><dt>انتهاء رخصة القارب</dt><dd>{{ $boat['boat_license_expiry_date'] ?? '—' }}</dd></div>
                    <div><dt>رقم الهيكل</dt><dd dir="ltr">{{ $boat['hull_number'] ?? '—' }}</dd></div>
                    <div><dt>رقم المحرك</dt><dd dir="ltr">{{ $boat['engine_number'] ?? '—' }}</dd></div>
                    <div><dt>الرقم التسلسلي للمحرك</dt><dd dir="ltr">{{ $boat['engine_serial_number'] ?? '—' }}</dd></div>
                    <div><dt>نداء الإشارة</dt><dd dir="ltr">{{ $boat['call_sign'] ?? '—' }}</dd></div>
                    <div><dt>رقم الرصيف</dt><dd dir="ltr">{{ ($boat['berth_number'] ?? null) ?: '—' }}</dd></div>
                    <div><dt>رقم الموقف</dt><dd dir="ltr">{{ ($boat['mooring_number'] ?? null) ?: '—' }}</dd></div>
                    <div><dt>الميناء</dt><dd>{{ $submission->port?->name ?? '—' }}</dd></div>
                    <div><dt>طريقة الصيد</dt><dd>{{ $fishingMethods[$submission->fishing_method] ?? $submission->fishing_method }}</dd></div>
                </dl>
            </div>

            <div role="tabpanel" id="panel-crew" aria-labelledby="tab-crew" class="info-admin-tabpanel" data-info-tabpanel="crew" hidden>
                <h2>بيانات الكابتن</h2>
                <dl class="info-admin-datagrid">
                    <div><dt>الاسم</dt><dd>{{ $captain['captain_full_name'] ?? $submission->captain?->full_name ?? '—' }}</dd></div>
                    <div><dt>رقم الهوية</dt><dd dir="ltr">{{ $captain['captain_national_id'] ?? '—' }}</dd></div>
                    <div><dt>رقم الجوال</dt><dd dir="ltr">{{ $captain['captain_phone'] ?? '—' }}</dd></div>
                    <div><dt>الجنسية</dt><dd>{{ $nationalities[$captain['captain_nationality'] ?? ''] ?? '—' }}</dd></div>
                    <div><dt>رخصة القيادة البحرية</dt><dd dir="ltr">{{ $captain['captain_license_number'] ?? '—' }}</dd></div>
                    <div><dt>انتهاء رخصة القيادة</dt><dd>{{ $captain['captain_license_expiry_date'] ?? '—' }}</dd></div>
                    <div><dt>رقم رخصة الصيد</dt><dd dir="ltr">{{ $captain['captain_fishing_license_number'] ?? '—' }}</dd></div>
                    <div><dt>إصدار رخصة الصيد</dt><dd>{{ $captain['captain_fishing_license_issue_date'] ?? '—' }}</dd></div>
                    <div><dt>انتهاء رخصة الصيد</dt><dd>{{ $captain['captain_fishing_license_expiry_date'] ?? '—' }}</dd></div>
                </dl>

                <h2>قائمة البحارة ({{ count($crewMembers) }})</h2>
                <div class="info-admin-table-scroll">
                    <table class="info-admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">الاسم</th>
                                <th scope="col">رقم الهوية</th>
                                <th scope="col">الجوال</th>
                                <th scope="col">الجنسية</th>
                                <th scope="col">الدور</th>
                                <th scope="col">رخصة الصيد</th>
                                <th scope="col">إصدارها</th>
                                <th scope="col">انتهاؤها</th>
                                <th scope="col">الصورة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($crewMembers as $index => $crewMember)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $crewMember['full_name'] ?? '—' }}</td>
                                    <td dir="ltr">{{ $crewMember['identity_number'] ?? '—' }}</td>
                                    <td dir="ltr">{{ $crewMember['phone'] ?? '—' }}</td>
                                    <td>{{ $nationalities[$crewMember['nationality'] ?? ''] ?? '—' }}</td>
                                    <td>{{ $crewRoles[$crewMember['role'] ?? ''] ?? '—' }}</td>
                                    <td dir="ltr">{{ ($crewMember['fishing_license_number'] ?? null) ?: '—' }}</td>
                                    <td>{{ ($crewMember['fishing_license_issue_date'] ?? null) ?: '—' }}</td>
                                    <td>{{ ($crewMember['fishing_license_expiry_date'] ?? null) ?: '—' }}</td>
                                    <td>
                                        @if (filled($crewMember['photo_path'] ?? null))
                                            <a class="info-admin-row-action" href="{{ route('information.admin.documents.show', [$submission, 'crew_photo_'.$index]) }}">تنزيل</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="info-admin-empty">لم يُسجَّل بحارة في هذا الطلب.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div role="tabpanel" id="panel-tools" aria-labelledby="tab-tools" class="info-admin-tabpanel" data-info-tabpanel="tools" hidden>
                <h2>أدوات الصيد</h2>
                <div class="info-admin-table-scroll">
                    <table class="info-admin-table">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">نوع الأداة</th>
                                <th scope="col">العدد</th>
                                <th scope="col">المقاس</th>
                                <th scope="col">المادة</th>
                                <th scope="col">الحالة</th>
                                <th scope="col">أساسية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($fishingTools as $index => $fishingTool)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $toolTypes[$fishingTool['type'] ?? ''] ?? '—' }}</td>
                                    <td>{{ $fishingTool['quantity'] ?? '—' }}</td>
                                    <td>{{ $fishingTool['size'] ?: '—' }}</td>
                                    <td>{{ $toolMaterials[$fishingTool['material'] ?? ''] ?? '—' }}</td>
                                    <td>{{ $toolConditions[$fishingTool['condition'] ?? ''] ?? '—' }}</td>
                                    <td>{{ ($fishingTool['is_primary'] ?? false) ? 'نعم' : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="info-admin-empty">لم تُسجَّل أدوات صيد في هذا الطلب.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div role="tabpanel" id="panel-documents" aria-labelledby="tab-documents" class="info-admin-tabpanel" data-info-tabpanel="documents" hidden>
                <h2>المستندات والمرفقات</h2>
                <ul class="info-admin-documents">
                    @foreach ($documentTypes as $category => $documentType)
                        @php $hasDocument = filled($documentPaths[$category] ?? null); @endphp
                        <li class="info-admin-document @if (! $hasDocument) is-missing @endif">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"></path><path d="M14 3v5h5"></path></svg>
                            <div>
                                <strong>{{ $documentType['label'] }}</strong>
                                <small>{{ $documentType['description'] }}</small>
                            </div>
                            @if ($hasDocument)
                                <a class="info-button info-button-secondary" href="{{ route('information.admin.documents.show', [$submission, $category]) }}">تنزيل</a>
                            @else
                                <span class="info-admin-document-missing">{{ ($documentType['required'] ?? false) ? 'مطلوب — غير مرفق' : 'غير مرفق' }}</span>
                            @endif
                        </li>
                    @endforeach

                    @if ($submission->captain_photo_path)
                        <li class="info-admin-document">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>
                            <div><strong>صورة الكابتن</strong><small>الصورة الشخصية المرفقة</small></div>
                            <a class="info-button info-button-secondary" href="{{ route('information.admin.documents.show', [$submission, 'captain_photo']) }}">تنزيل</a>
                        </li>
                    @endif
                </ul>
            </div>

            <div role="tabpanel" id="panel-activity" aria-labelledby="tab-activity" class="info-admin-tabpanel" data-info-tabpanel="activity" hidden>
                <h2>سجل النشاط</h2>
                <ol class="info-admin-activity">
                    @forelse ($submission->events as $event)
                        <li>
                            <span class="info-admin-activity-dot" aria-hidden="true"></span>
                            <div>
                                <strong>
                                    @if ($event->event_type === 'submitted')
                                        تم إرسال الطلب
                                    @elseif ($event->from_status && $event->from_status !== $event->to_status)
                                        {{ \App\Models\InformationSubmission::STATUS_LABELS[$event->from_status] ?? $event->from_status }}
                                        ← {{ \App\Models\InformationSubmission::STATUS_LABELS[$event->to_status] ?? $event->to_status }}
                                    @else
                                        تحديث ملاحظات المراجعة
                                    @endif
                                </strong>
                                <small>
                                    <time datetime="{{ $event->created_at?->toIso8601String() }}">{{ $event->created_at?->format('Y/m/d — H:i') }}</time>
                                    @if ($event->actor) · {{ $event->actor->full_name }} @endif
                                </small>
                                @if ($event->note)
                                    <p>{{ $event->note }}</p>
                                @endif
                            </div>
                        </li>
                    @empty
                        <li class="info-admin-empty">لا يوجد نشاط مسجل بعد.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </section>

    <aside class="info-form-card info-admin-review" aria-labelledby="review-title">
        <h2 id="review-title">قرار المراجعة</h2>

        <dl class="info-admin-review-meta">
            <div><dt>الحالة الحالية</dt><dd><x-information.status-chip :status="$submission->status" /></dd></div>
            @if ($submission->reviewer)
                <div><dt>آخر مراجعة</dt><dd>{{ $submission->reviewer->full_name }}</dd></div>
                <div><dt>تاريخ المراجعة</dt><dd>{{ $submission->reviewed_at?->format('Y/m/d — H:i') }}</dd></div>
            @endif
            @if ($submission->submitter)
                <div><dt>أُرسل بواسطة</dt><dd>{{ $submission->submitter->full_name }}</dd></div>
            @endif
        </dl>

        @if ($submission->review_notes)
            <div class="info-admin-review-note">
                <strong>آخر ملاحظة</strong>
                <p>{{ $submission->review_notes }}</p>
            </div>
        @endif

        <form method="post" action="{{ route('information.admin.review', $submission) }}">
            @csrf
            @method('patch')

            <div class="info-field">
                <label for="review_notes">ملاحظات الموظف</label>
                <textarea id="review_notes" name="review_notes" rows="5" placeholder="اكتب ملاحظاتك هنا…"
                          aria-invalid="{{ $errors->has('review_notes') ? 'true' : 'false' }}">{{ old('review_notes', $submission->review_notes) }}</textarea>
                @error('review_notes')
                    <p class="info-field-error">{{ $message }}</p>
                @enderror
                <p class="info-field-hint">الملاحظة إلزامية عند طلب التعديل أو الرفض، وتظهر لمقدّم الطلب في صفحة متابعة الحالة.</p>
            </div>

            @error('status')
                <p class="info-field-error">{{ $message }}</p>
            @enderror

            <div class="info-admin-review-actions">
                @forelse ($allowedStatuses as $allowedStatus)
                    <button class="info-button info-admin-decision" data-tone="{{ $statusActions[$allowedStatus]['tone'] }}"
                            type="submit" name="status" value="{{ $allowedStatus }}">
                        {{ $statusActions[$allowedStatus]['label'] }}
                    </button>
                @empty
                    <p class="info-field-hint">لا توجد إجراءات متاحة على هذا الطلب.</p>
                @endforelse

                <button class="info-button info-button-secondary" type="submit" name="status" value="{{ $submission->status }}">
                    حفظ الملاحظة فقط
                </button>
            </div>
        </form>
    </aside>
</div>
@endsection
