@if ($paginator->hasPages())
    <nav>
        @if ($paginator->onFirstPage())
            <span>السابق</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}">السابق</a>
        @endif

        <span>صفحة {{ $paginator->currentPage() }} من {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}">التالي</a>
        @else
            <span>التالي</span>
        @endif
    </nav>
@endif