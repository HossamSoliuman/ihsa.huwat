{{-- ترقيم الصفحات على قاعدة الأزرار نفسها: سابق، موضع الصفحة، تالي. --}}
@if ($paginator->hasPages())
    <nav class="pager" aria-label="ترقيم الصفحات">
        @if ($paginator->onFirstPage())
            <span class="btn btn-outline is-disabled">السابق</span>
        @else
            <a class="btn btn-outline" href="{{ $paginator->previousPageUrl() }}">السابق</a>
        @endif

        <span class="pager-pos">صفحة {{ $paginator->currentPage() }} من {{ $paginator->lastPage() }} — {{ number_format($paginator->total()) }} سجل</span>

        @if ($paginator->hasMorePages())
            <a class="btn btn-outline" href="{{ $paginator->nextPageUrl() }}">التالي</a>
        @else
            <span class="btn btn-outline is-disabled">التالي</span>
        @endif
    </nav>
@endif
