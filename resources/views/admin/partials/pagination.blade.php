@if ($paginator->hasPages())
    <nav aria-label="الصفحات">
        @if ($paginator->onFirstPage())
            <span class="is-disabled">السابق</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">السابق</a>
        @endif

        <span>صفحة {{ $paginator->currentPage() }} من {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">التالي</a>
        @else
            <span class="is-disabled">التالي</span>
        @endif
    </nav>
@endif
