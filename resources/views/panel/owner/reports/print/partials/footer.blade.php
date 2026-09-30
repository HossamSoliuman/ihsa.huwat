{{-- تذييل التقرير: المالك والحقوق، وملاحظة العملة إن مُرِّرت. --}}
<table class="report-footer">
    <tr>
        <td>{{ $owner->name }} — جميع الحقوق محفوظة © {{ $year ?? date('Y') }}@if (! empty($note)) · {{ $note }}@endif</td>
    </tr>
</table>
