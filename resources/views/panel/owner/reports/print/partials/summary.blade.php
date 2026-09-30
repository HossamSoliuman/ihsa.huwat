{{--
    صندوق المجاميع أسفل التقرير (report-summary في hispa): سطر لكل مجموع،
    و`highlight` للسطر الأخير البارز. كل سطر: label، value، و`money` لمبلغ.
--}}
<div class="bottom-section">
    @foreach ($rows as $row)
        <table class="summary-row {{ ($row['highlight'] ?? false) ? 'highlight' : '' }}">
            <tr>
                <td>{{ $row['label'] }}</td>
                <td>@if ($row['money'] ?? false){{ \App\Services\Owner\OwnerReports::money($row['value']) }}@else<bdi dir="ltr">{{ $row['value'] }}</bdi>@endif</td>
            </tr>
        </table>
    @endforeach
</div>
