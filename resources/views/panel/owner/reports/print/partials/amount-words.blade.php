{{-- المبلغ كتابةً (report-amount-words في hispa): شريط عنوان أسود فوق المبلغ بالحروف. --}}
<table class="amount-words">
    <tr><td class="aw-cap">{{ $label ?? 'المبلغ كتابة' }}</td></tr>
    <tr><td class="aw-text">{{ \App\Support\AmountInWords::riyals($amount) }}</td></tr>
</table>
