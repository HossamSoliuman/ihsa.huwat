{{-- رابط التقديم العام الذي تنشره الشركة للعدّادين، بزر نسخ. --}}
<div class="card apply-link">
    <div class="apply-link-label">@include('partials.icon', ['name' => 'link']) رابط التقديم للعدّادين</div>
    <div class="apply-link-row">
        <input class="input num" id="applyLinkInput" type="text" dir="ltr" readonly value="{{ route('counter-apply') }}" onclick="this.select()">
        <button type="button" class="btn btn-primary" id="applyLinkCopy" onclick="copyApplyLink()">@include('partials.icon', ['name' => 'clipboard']) <span>نسخ</span></button>
        <a href="{{ route('counter-apply') }}" target="_blank" rel="noopener" class="btn btn-outline" title="فتح">@include('partials.icon', ['name' => 'external-link'])</a>
    </div>
</div>

@once
@push('scripts')
<script>
    function copyApplyLink() {
        const input = document.getElementById('applyLinkInput');
        const label = document.querySelector('#applyLinkCopy span');
        const done = () => { label.textContent = 'تم النسخ'; setTimeout(() => label.textContent = 'نسخ', 2000); };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(input.value).then(done);
        } else {
            input.select();
            document.execCommand('copy');
            done();
        }
    }
</script>
@endpush
<style>
    .apply-link { margin-bottom: 1.25rem; }
    .apply-link-label { display: flex; align-items: center; gap: .5rem; font-weight: 700; margin-bottom: .6rem; }
    .apply-link-row { display: flex; gap: .5rem; }
    .apply-link-row .input { flex: 1; min-width: 0; font-size: 1rem; font-weight: 600; text-align: left; }
    .apply-link-row .btn { flex-shrink: 0; }
</style>
@endonce
