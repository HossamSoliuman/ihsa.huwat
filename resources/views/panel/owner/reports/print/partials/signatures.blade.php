{{-- شريط التوقيعات (report-signatures في hispa): عناوين متساوية المسافة فوق خطوط منقّطة. --}}
<table class="sig-table">
    <tr>
        @foreach ($items as $label)
            <td style="width:{{ round(100 / count($items), 4) }}%">
                <div class="sig-label">{{ $label }}</div>
                <div class="sig-line">.............................</div>
            </td>
        @endforeach
    </tr>
</table>
