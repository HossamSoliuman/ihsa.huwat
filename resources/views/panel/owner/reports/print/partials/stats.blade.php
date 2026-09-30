{{--
    بطاقات المؤشرات المطبوعة (report-stats في hispa): عنوان فوق قيمة محدّدة.
    كل بند: label، value، و`money` لمبلغ، و`tone` = good|bad لتلوينه. أكثر من
    خمس بطاقات في ورقة طولية (سبع في العرضية) تُصغَّر أرقامها لتتسع للهامش.
--}}
@php $cards = array_values(array_filter($items)); @endphp
@if ($cards)
    <table class="report-stats {{ count($cards) > (($landscape ?? false) ? 7 : 5) ? 'dense' : '' }}">
        <tr>
            @foreach ($cards as $card)
                <td>
                    <div class="report-stat-label">{{ $card['label'] }}</div>
                    <div class="report-stat-value {{ isset($card['tone']) ? 'tx-'.$card['tone'] : '' }}">
                        @if (! empty($card['money'])){{ \App\Services\Owner\OwnerReports::money($card['value']) }}@else<bdi dir="ltr">{{ $card['value'] }}</bdi>@endif
                    </div>
                </td>
            @endforeach
        </tr>
    </table>
@endif
