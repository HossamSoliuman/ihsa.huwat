{{-- قائمة الأرباح في الويب: البنود نفسها المطبوعة، بجدول يقرأ عموديًا. --}}
<div class="table-card" style="border:0">
    <table class="data-table">
        <tbody>
            @foreach ($lines as $line)
                @php
                    $style = match ($line['type']) {
                        'hd' => 'background:hsl(var(--primary) / .12);font-weight:700;color:hsl(var(--primary))',
                        'total' => 'font-weight:700;background:hsl(var(--muted) / .5)',
                        'grand' => 'font-weight:800;background:hsl(var(--primary));color:hsl(var(--primary-foreground))',
                        default => '',
                    };
                @endphp
                <tr style="{{ $style }}">
                    <td style="{{ $line['type'] === 'sub' ? 'padding-inline-start:2rem;color:hsl(var(--muted-foreground))' : '' }}">
                        {{ $line['label'] }}
                        @if (! empty($line['hint']))<span style="font-size:.72rem;opacity:.75;font-weight:400"> — {{ $line['hint'] }}</span>@endif
                    </td>
                    <td class="num" dir="ltr" style="text-align:left;white-space:nowrap">
                        @if ($line['amount'] !== null)
                            {{ $line['amount'] < 0 ? '('.number_format(abs($line['amount']), 2).')' : number_format($line['amount'], 2) }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
