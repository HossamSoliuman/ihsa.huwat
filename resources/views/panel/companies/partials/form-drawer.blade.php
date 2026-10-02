{{-- نموذج الشركة (إنشاء وتعديل) — تفتحه openCompanyForm(company?) من القائمة ومن صفحة الشركة. --}}
<div class="drawer-overlay" id="companyDrawer-overlay" onclick="toggleDrawer('companyDrawer', false)"></div>
<div class="drawer" id="companyDrawer">
    <div class="drawer-head">
        <h3 id="companyFormTitle">شركة جديدة</h3>
        <button type="button" class="icon-action" onclick="toggleDrawer('companyDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
    </div>
    <form method="POST" id="companyForm" action="{{ route('panel.companies.store') }}" class="drawer-body" autocomplete="off">
        @csrf
        <input type="hidden" name="_method" id="companyMethod" value="POST">
        <input type="hidden" name="id" id="c-id" value="">
        <div class="form-grid cols-2">
            <label class="field" style="grid-column:1/-1"><span>اسم الشركة *</span><input class="input" name="name" id="c-name" required maxlength="255"></label>
            <label class="field"><span>السجل التجاري</span><input class="input" name="commercial_register" id="c-cr" dir="ltr" maxlength="20"></label>
            <label class="field"><span>الحالة *</span>
                <select class="select" name="status" id="c-status" required>
                    @foreach (\App\Models\OperatingCompany::STATUS_LABELS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
                </select>
            </label>
            <label class="field"><span>اسم المسؤول</span><input class="input" name="contact_name" id="c-contact" maxlength="255"></label>
            <label class="field"><span>جوال الشركة</span><input class="input" name="phone" id="c-phone" dir="ltr" inputmode="tel" placeholder="05XXXXXXXX"></label>
            <label class="field" style="grid-column:1/-1"><span>البريد الإلكتروني</span><input class="input" type="email" name="email" id="c-email" dir="ltr"></label>
            <label class="field" style="grid-column:1/-1"><span>ملاحظات</span><textarea class="input" name="notes" id="c-notes" rows="3" maxlength="2000"></textarea></label>
        </div>
        <p class="card-sub" style="margin:0">الشركة الموقوفة لا يدخل موظفوها بوابتها ولا تُقبل طلبات في جولاتها، ويبقى عدّادوها العاملون كما هم.</p>
        <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
            <button type="button" class="btn btn-outline" onclick="toggleDrawer('companyDrawer', false)">إلغاء</button>
            <button type="submit" class="btn btn-primary">حفظ</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const companyStoreUrl = @json(route('panel.companies.store'));

    function openCompanyForm(company = null) {
        const form = document.getElementById('companyForm');
        document.getElementById('companyFormTitle').textContent = company?.id ? 'تعديل الشركة' : 'شركة جديدة';
        document.getElementById('companyMethod').value = company?.id ? 'PUT' : 'POST';
        form.action = company?.id ? companyStoreUrl + '/' + company.id : companyStoreUrl;
        document.getElementById('c-id').value = company?.id ?? '';
        document.getElementById('c-name').value = company?.name ?? '';
        document.getElementById('c-cr').value = company?.commercial_register ?? '';
        document.getElementById('c-status').value = company?.status ?? 'active';
        document.getElementById('c-contact').value = company?.contact_name ?? '';
        document.getElementById('c-phone').value = company?.phone ?? '';
        document.getElementById('c-email').value = company?.email ?? '';
        document.getElementById('c-notes').value = company?.notes ?? '';
        toggleDrawer('companyDrawer', true);
    }

    @if ($errors->any() && old('name') !== null && old('status') !== null)
        openCompanyForm({!! json_encode(['id' => old('id') ?: null] + old(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!});
    @endif
</script>
@endpush
