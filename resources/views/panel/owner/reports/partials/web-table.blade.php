{{-- جدول تقرير في الويب — البنية نفسها التي تُطبع وتُصدَّر. --}}
<div class="card" style="margin-bottom:1.25rem">
    @if ($table['title'] ?? null)
        @include('partials.section-head', ['icon' => $icon ?? 'list-checks', 'title' => $table['title'], 'note' => $note ?? null])
    @endif
    <div class="table-card" style="border:0">
        <table class="data-table">
            <thead>
                <tr>@foreach ($table['columns'] as $column)<th>{{ $column['label'] }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @forelse ($table['rows'] as $row)
                    <tr @if ($row['_dim'] ?? false) style="opacity:.5" @endif>
                        @foreach ($table['columns'] as $column)
                            @include('panel.owner.reports.partials.cell', ['web' => true, 'first' => $loop->first])
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($table['columns']) }}" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">{{ $empty ?? 'لا بيانات في هذه الفترة' }}</td></tr>
                @endforelse
            </tbody>
            @if ($table['totals'])
                <tfoot>
                    <tr style="font-weight:700;background:hsl(var(--muted) / .5)">
                        @foreach ($table['columns'] as $column)
                            @if ($loop->first)
                                <td>الإجمالي</td>
                            @elseif (array_key_exists($column['key'], $table['totals']))
                                @include('panel.owner.reports.partials.cell', ['row' => $table['totals'], 'web' => true, 'first' => false])
                            @else
                                <td></td>
                            @endif
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
