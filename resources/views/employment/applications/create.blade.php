@extends('layouts.employment')

@section('title', 'التقديم على '.$job->title_ar)
@section('body-class', 'employment-application-page employment-hud-public')

@section('content')
<section class="employment-page-hero employment-application-hero"><div class="employment-container"><a class="employment-back-link" href="{{ route('jobs.show', $job) }}">تفاصيل الوظيفة</a><p class="employment-eyebrow">طلب توظيف جديد</p><h1>التقديم على {{ $job->title_ar }}</h1><p>أكمل البيانات التالية وارفع المستندات المطلوبة. الحقول المميزة بنجمة إلزامية.</p></div></section>
<section class="employment-container employment-application-shell">
    @if($errors->any())<div class="employment-alert employment-alert-error" role="alert"><strong>راجع البيانات المدخلة</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="post" action="{{ route('applications.store', $job) }}" enctype="multipart/form-data" class="employment-application-form" data-application-form>
        @csrf
        <input class="employment-honeypot" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">

        <section class="employment-form-panel is-active" data-step-panel="1">
            <div class="employment-panel-heading"><div><span>الخطوة الأولى</span><h2>البيانات الشخصية والاتصال</h2><p>أدخل البيانات كما تظهر في وثائقك الرسمية.</p></div></div>
            <div class="employment-form-grid employment-form-grid-three">
                <div class="employment-field employment-field-wide"><label for="full_name">الاسم الكامل *</label><input id="full_name" name="full_name" value="{{ old('full_name') }}" required maxlength="150" autocomplete="name"></div>
                <div class="employment-field"><label for="nationality">الجنسية *</label><input id="nationality" name="nationality" value="{{ old('nationality') }}" required maxlength="100"></div>
                <div class="employment-field"><label for="identity_type">نوع الهوية *</label><select id="identity_type" name="identity_type" required>@foreach(config('employment.identity_types') as $value=>$label)<option value="{{ $value }}" @selected(old('identity_type')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="employment-field"><label for="identity_number">رقم الهوية *</label><input id="identity_number" name="identity_number" value="{{ old('identity_number') }}" required maxlength="50" dir="ltr"></div>
                <div class="employment-field"><label for="birth_date">تاريخ الميلاد *</label><input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date') }}" required></div>
                <div class="employment-field"><label for="gender">الجنس *</label><select id="gender" name="gender" required><option value="male" @selected(old('gender')==='male')>ذكر</option><option value="female" @selected(old('gender')==='female')>أنثى</option></select></div>
                <div class="employment-field"><label for="marital_status">الحالة الاجتماعية *</label><select id="marital_status" name="marital_status" required>@foreach(['single'=>'أعزب/عزباء','married'=>'متزوج/ة','divorced'=>'مطلق/ة','widowed'=>'أرمل/ة'] as $value=>$label)<option value="{{ $value }}" @selected(old('marital_status')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="employment-field"><label for="children_count">عدد الأبناء *</label><input type="number" id="children_count" name="children_count" value="{{ old('children_count',0) }}" min="0" max="30" required></div>
                <div class="employment-field"><label for="mobile">الجوال *</label><input id="mobile" name="mobile" value="{{ old('mobile') }}" required maxlength="15" dir="ltr" autocomplete="tel"></div>
                <div class="employment-field"><label for="phone">هاتف إضافي</label><input id="phone" name="phone" value="{{ old('phone') }}" maxlength="15" dir="ltr"></div>
                <div class="employment-field"><label for="email">البريد الإلكتروني *</label><input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="150" dir="ltr" autocomplete="email"></div>
                <div class="employment-field"><label for="city">المدينة *</label><input id="city" name="city" value="{{ old('city') }}" required maxlength="120"></div>
                <div class="employment-field employment-field-full"><label for="address">العنوان *</label><textarea id="address" name="address" required maxlength="1000">{{ old('address') }}</textarea></div>
                <div class="employment-field"><label for="preferred_port_id">الميناء المفضل</label><select id="preferred_port_id" name="preferred_port_id"><option value="">غير محدد</option>@foreach($ports as $port)<option value="{{ $port->id }}" @selected((string)old('preferred_port_id')===(string)$port->id)>{{ $port->name }}</option>@endforeach</select></div>
                <div class="employment-field"><label for="work_type">نوع الدوام *</label><select id="work_type" name="work_type" required>@foreach(config('employment.types') as $value=>$label)<option value="{{ $value }}" @selected(old('work_type',$job->employment_type)===$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="employment-field"><label for="source">كيف عرفت عن الوظيفة؟ *</label><select id="source" name="source" required>@foreach(config('employment.sources') as $value=>$label)<option value="{{ $value }}" @selected(old('source')===$value)>{{ $label }}</option>@endforeach</select></div>
            </div>
            <div class="employment-form-actions"><button class="employment-button employment-button-primary" type="button" data-wizard-next>التالي ←</button></div>
        </section>

        <section class="employment-form-panel" data-step-panel="2">
            <div class="employment-panel-heading"><div><span>الخطوة الثانية</span><h2>المؤهلات والخبرة</h2><p>عرّفنا بخلفيتك الأكاديمية وخبرتك العملية.</p></div></div>
            <div class="employment-form-grid employment-form-grid-three">
                <div class="employment-field"><label for="education_level">المستوى التعليمي *</label><select id="education_level" name="education_level" required>@foreach(config('employment.education_levels') as $value=>$label)<option value="{{ $value }}" @selected(old('education_level')===$value)>{{ $label }}</option>@endforeach</select></div>
                <div class="employment-field"><label for="specialization">التخصص *</label><input id="specialization" name="specialization" value="{{ old('specialization') }}" required maxlength="190"></div>
                <div class="employment-field"><label for="institution">الجهة التعليمية *</label><input id="institution" name="institution" value="{{ old('institution') }}" required maxlength="190"></div>
                <div class="employment-field"><label for="graduation_year">سنة التخرج</label><input type="number" id="graduation_year" name="graduation_year" value="{{ old('graduation_year') }}" min="1950" max="{{ today()->year+1 }}"></div>
                <div class="employment-field"><label for="experience_years">سنوات الخبرة *</label><input type="number" id="experience_years" name="experience_years" value="{{ old('experience_years',0) }}" min="0" max="60" step="0.5" required></div>
                <div class="employment-field"><label for="availability_date">تاريخ الجاهزية</label><input type="date" id="availability_date" name="availability_date" value="{{ old('availability_date') }}" min="{{ today()->format('Y-m-d') }}"></div>
                <div class="employment-field"><label for="current_employer">جهة العمل الحالية</label><input id="current_employer" name="current_employer" value="{{ old('current_employer') }}" maxlength="190"></div>
                <div class="employment-field"><label for="current_job_title">المسمى الحالي</label><input id="current_job_title" name="current_job_title" value="{{ old('current_job_title') }}" maxlength="190"></div>
                <div class="employment-field employment-field-full"><label for="professional_summary">ملخص الخبرة المهنية</label><textarea id="professional_summary" name="professional_summary" rows="5" maxlength="3000">{{ old('professional_summary') }}</textarea></div>
                <div class="employment-field employment-field-full"><label for="skills">المهارات *</label><textarea id="skills" name="skills" rows="4" maxlength="3000" required>{{ old('skills') }}</textarea></div>
                <div class="employment-field employment-field-full"><label for="cover_letter">رسالة تعريفية</label><textarea id="cover_letter" name="cover_letter" rows="5" maxlength="5000">{{ old('cover_letter') }}</textarea></div>
            </div>
            <div class="employment-form-actions"><button class="employment-button employment-button-quiet" type="button" data-wizard-previous>→ السابق</button><button class="employment-button employment-button-primary" type="button" data-wizard-next>التالي ←</button></div>
        </section>

        <section class="employment-form-panel" data-step-panel="3">
            <div class="employment-panel-heading"><div><span>الخطوة الثالثة</span><h2>المرفقات</h2><p>PDF أو JPEG أو PNG، وبحد أقصى 10 ميجابايت لكل ملف.</p></div></div>
            <div class="employment-upload-grid">
                <div class="employment-field employment-upload-field" data-upload-zone><label for="cv_file"><strong>السيرة الذاتية *</strong><small>ملف واحد مطلوب</small><span class="employment-upload-action">اختر ملفاً أو اسحبه هنا</span></label><input id="cv_file" name="cv_file" type="file" required accept=".pdf,.jpg,.jpeg,.png"><ul class="employment-file-list" data-file-list="cv_file"></ul></div>
                <div class="employment-field employment-upload-field" data-upload-zone><label for="identity_file"><strong>صورة الهوية</strong><small>اختياري</small><span class="employment-upload-action">اختر ملفاً</span></label><input id="identity_file" name="identity_file" type="file" accept=".pdf,.jpg,.jpeg,.png"><ul class="employment-file-list" data-file-list="identity_file"></ul></div>
                <div class="employment-field employment-upload-field employment-upload-wide" data-upload-zone><label for="certificate_files"><strong>الشهادات والمؤهلات</strong><small>اختياري — حتى 5 ملفات</small><span class="employment-upload-action">اختر الملفات</span></label><input id="certificate_files" name="certificate_files[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png"><ul class="employment-file-list" data-file-list="certificate_files"></ul></div>
            </div>
            <div class="employment-security-note"><div><strong>مرفقاتك محمية</strong><p>تُحفظ خارج المسار العام ولا يطّلع عليها إلا فريق التوظيف المخوّل.</p></div></div>
            <div class="employment-form-actions"><button class="employment-button employment-button-quiet" type="button" data-wizard-previous>→ السابق</button><button class="employment-button employment-button-primary" type="button" data-wizard-next>المراجعة ←</button></div>
        </section>

        <section class="employment-form-panel" data-step-panel="4">
            <div class="employment-panel-heading"><div><span>الخطوة الرابعة</span><h2>مراجعة وإرسال الطلب</h2><p>يمكنك الرجوع إلى الخطوات السابقة لتعديل أي بيانات.</p></div></div>
            <div class="employment-review-grid"><section><h3>الوظيفة</h3><p>{{ $job->title_ar }}</p><small>{{ $job->reference_no }}</small></section><section><h3>حماية البيانات</h3><p>تعالج بياناتك فقط لأغراض التوظيف والتحقق من المؤهلات.</p></section></div>
            <label class="employment-consent-box" for="consent"><input id="consent" name="consent" type="checkbox" value="1" @checked(old('consent')) required><span><strong>إقرار بصحة البيانات *</strong><small>أقر بأن جميع البيانات والمرفقات صحيحة وأوافق على استخدامها لأغراض التوظيف.</small></span></label>
            <div class="employment-form-actions"><button class="employment-button employment-button-quiet" type="button" data-wizard-previous>→ السابق</button><button class="employment-button employment-button-primary employment-submit-button" type="submit" data-application-submit>إرسال الطلب</button></div>
        </section>
    </form>
</section>
@endsection
