{{-- معلومات التقرير (report-info في hispa): تاريخ الإنشاء، ثم الفترة والقارب إن اختير. --}}
<div class="info-section">
    <div class="info-label">معلومات التقرير</div>
    <p><strong>تاريخ الإنشاء:</strong> <bdi dir="ltr">{{ now()->format('Y-m-d H:i:s') }}</bdi></p>
    <div class="period-info">
        <strong>من تاريخ:</strong> <bdi dir="ltr">{{ $from }}</bdi>
        <strong style="margin-inline-start:20px">إلى تاريخ:</strong> <bdi dir="ltr">{{ $to }}</bdi>
        @if ($boat ?? null)
            <br><strong>القارب:</strong> {{ $boat->name }}
        @endif
    </div>
</div>
