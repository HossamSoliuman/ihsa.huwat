@if (! empty($notes))
    <div class="card" style="font-size:.8rem;color:hsl(var(--muted-foreground))">
        @include('partials.section-head', ['icon' => 'book-open', 'title' => 'كيف تُحسب الأرقام'])
        <ul style="margin:0;padding-inline-start:1.1rem;display:grid;gap:.3rem">
            @foreach ($notes as $note)<li>{{ $note }}</li>@endforeach
        </ul>
    </div>
@endif
