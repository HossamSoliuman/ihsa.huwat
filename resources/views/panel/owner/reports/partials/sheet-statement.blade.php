{{-- قائمة الأرباح المطبوعة: أقسام بعنوان، بنود وبنود فرعية، مجموع كل قسم، ونتيجتان بارزتان. --}}
<div class="statement">
    @php $blocks = []; $current = []; @endphp
    @foreach ($lines as $line)
        @php
            if ($line['type'] === 'hd' && $current !== []) { $blocks[] = $current; $current = []; }
            $current[] = $line;
            if ($line['type'] === 'grand') { $blocks[] = $current; $current = []; }
        @endphp
    @endforeach
    @php if ($current !== []) { $blocks[] = $current; } @endphp

    @foreach ($blocks as $block)
        <div class="block">
            @foreach ($block as $line)
                <div class="row {{ $line['type'] === 'hd' ? 'hd' : $line['type'] }}">
                    <span>{{ $line['label'] }}@if (! empty($line['hint'])) <span class="hint">— {{ $line['hint'] }}</span>@endif</span>
                    @if ($line['amount'] !== null)
                        <span class="num" dir="ltr">{{ $line['amount'] < 0 ? '('.number_format(abs($line['amount']), 2).')' : number_format($line['amount'], 2) }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endforeach
</div>
<p class="muted" style="margin-top:4px">المبالغ بالريال السعودي؛ ما بين قوسين مطروح.</p>
