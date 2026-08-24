<div class="b-table-wrap">
    <table class="b-table">
        <thead>
            <tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td class="empty" colspan="{{ count($headers) }}">لا توجد بيانات لهذه السنة</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
