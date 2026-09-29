{{-- جدول تقرير للطباعة: رأس يتكرر في كل صفحة، وسطر مجموع واحد في آخره. --}}
@if ($table['title'] ?? null)
    <div class="section">{{ $table['title'] }}</div>
@endif
<table>
    <thead>
        <tr>
            @foreach ($table['columns'] as $column)
                <th @class(['strong' => $column['strong']])>{{ $column['label'] }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse ($table['rows'] as $row)
            <tr @class(['dim' => $row['_dim'] ?? false])>
                @foreach ($table['columns'] as $column)
                    @include('panel.owner.reports.partials.cell', ['first' => $loop->first])
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{ count($table['columns']) }}" style="text-align:center;padding:18px;color:#64748b">{{ $empty ?? 'لا بيانات في هذه الفترة' }}</td></tr>
        @endforelse
    </tbody>
    @if ($table['totals'])
        <tfoot>
            <tr>
                @foreach ($table['columns'] as $column)
                    @if ($loop->first)
                        <td>الإجمالي</td>
                    @elseif (array_key_exists($column['key'], $table['totals']))
                        @include('panel.owner.reports.partials.cell', ['row' => $table['totals'], 'first' => false])
                    @else
                        <td></td>
                    @endif
                @endforeach
            </tr>
        </tfoot>
    @endif
</table>
