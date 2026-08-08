<x-information.section-head title="اكتمال البيانات" subtitle="سجلات تحتاج استكمال أطفالها أو مستنداتها" icon="bell" />

<section class="info-completeness-grid" aria-label="مؤشرات اكتمال البيانات">
    @foreach ($dataCompleteness as $issue)
        <article class="info-completeness-card" @if ($issue['count'] > 0) data-tone="warn" @endif>
            <header>
                <h3>{{ $issue['label'] }}</h3>
                <strong>{{ number_format($issue['count']) }}</strong>
            </header>
            @if (count($issue['items']) === 0)
                <p>لا توجد سجلات ناقصة.</p>
            @else
                <ul>
                    @foreach ($issue['items'] as $item)
                        <li><a href="{{ $item['href'] }}">{{ $item['label'] }}</a></li>
                    @endforeach
                </ul>
            @endif
        </article>
    @endforeach
</section>
