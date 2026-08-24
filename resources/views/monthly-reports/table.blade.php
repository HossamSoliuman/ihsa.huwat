<div class="table-card" style="margin-bottom:1rem">
    <p class="card-title" style="padding:.75rem 1rem;border-bottom:1px solid hsl(var(--border))">{{ $title }}</p>
    <table class="data-table">
        <thead>
            <tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>@foreach ($row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) }}" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا توجد بيانات</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
