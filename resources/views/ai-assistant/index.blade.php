@extends('layouts.app')

@section('title', 'حوات AI')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'bot'])</div>
            <div>
                <h1>حوات AI</h1>
            </div>
        </div>
        @if ($insight)
            <div class="actions">
                <a href="{{ route('stats.ai-assistant') }}" class="btn btn-outline">@include('partials.icon', ['name' => 'trash']) سؤال جديد</a>
            </div>
        @endif
    </div>

    <div class="chat">
        <div class="chat-row">
            <div class="chat-avatar">@include('partials.icon', ['name' => 'sparkles'])</div>
            <div class="chat-bubble bot">
                مرحبًا! أنا حوات AI للتحليل التنفيذي. اسألني عن الإنتاج، الرحلات، الموانئ، الأنواع، الفروقات، مواقع الصيد أو ملخص القطاع.
            </div>
        </div>

        @if ($insight)
            <div class="chat-row me">
                <div class="chat-avatar me">@include('partials.icon', ['name' => 'user'])</div>
                <div class="chat-bubble">{{ $question }}</div>
            </div>

            <div class="chat-row">
                <div class="chat-avatar">@include('partials.icon', ['name' => 'sparkles'])</div>
                <div class="insight">
                    <div class="insight-head">
                        <p class="kicker">HAWAT AI INSIGHT</p>
                        <h3>{{ $insight['title'] }}</h3>
                        <p class="answer">{{ $insight['direct_answer'] }}</p>
                        <div class="insight-meta">
                            <span>@include('partials.icon', ['name' => 'shield-check']) النطاق: المملكة</span>
                            <span>@include('partials.icon', ['name' => 'clock']) البيانات حتى {{ $insight['data_as_of']->format('Y-m-d H:i') }}</span>
                            <span>@include('partials.icon', ['name' => 'database']) {{ number_format($insight['rows_considered']) }} سجل</span>
                            <span>@include('partials.icon', ['name' => 'check-circle']) الثقة: {{ ['high' => 'عالية', 'medium' => 'متوسطة', 'low' => 'منخفضة'][$insight['confidence']] }}</span>
                        </div>
                        @if ($insight['unmatched'])
                            <p style="margin-top:.75rem;font-size:11px;color:hsl(var(--muted-foreground))">لم يطابق السؤال أيًا من الموضوعات المعروفة، فأُجيب بالملخص التنفيذي للقطاع.</p>
                        @endif
                    </div>

                    @if ($insight['kpis'])
                        <div class="stat-grid cols-4">
                            @foreach ($insight['kpis'] as $kpi)
                                <div class="stat-card">
                                    <div style="min-width:0">
                                        <p class="label">{{ $kpi['label'] }}</p>
                                        <p class="value">{{ $kpi['value'] }}@if (!empty($kpi['unit']))<span class="unit">{{ $kpi['unit'] }}</span>@endif</p>
                                        @if (isset($kpi['change_pct']))
                                            <p style="margin-top:.25rem">
                                                @include('performance-compare.delta', ['delta' => $kpi['change_pct'], 'extreme' => false, 'low' => $kpi['change_pct'] < 0, 'suffix' => '%'])
                                            </p>
                                        @endif
                                    </div>
                                    <div class="kpi-icon {{ ['good' => 'success', 'warning' => 'warning', 'critical' => 'danger'][$kpi['status']] ?? 'primary' }}">
                                        @include('partials.icon', ['name' => 'activity'])
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($insight['chart']['labels'])
                        <div class="card">
                            <p class="card-title">{{ $insight['chart']['title'] }}</p>
                            <p class="card-sub" style="margin-bottom:.75rem">{{ $insight['chart']['unit'] }}</p>
                            <div class="chart-wrap" style="height:260px"><canvas id="insightChart"></canvas></div>
                        </div>
                    @endif

                    @if ($insight['drivers'] || $insight['recommendations'])
                        <div class="grid-2">
                            @if ($insight['drivers'])
                                <div class="note-list drivers">
                                    <h4>الأسباب المحتملة / المحرّكات</h4>
                                    <ul style="padding-inline-start:1.1rem">
                                        @foreach ($insight['drivers'] as $driver)<li>{{ $driver }}</li>@endforeach
                                    </ul>
                                </div>
                            @endif
                            @if ($insight['recommendations'])
                                <div class="note-list actions">
                                    <h4>إجراءات مقترحة</h4>
                                    <ol style="padding-inline-start:1.1rem">
                                        @foreach ($insight['recommendations'] as $action)<li>{{ $action }}</li>@endforeach
                                    </ol>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        @unless ($insight)
            <div>
                <p style="margin-bottom:.5rem;font-size:.72rem;font-weight:500;color:hsl(var(--muted-foreground))">أسئلة مقترحة</p>
                <div class="suggestions">
                    @foreach ($suggestions as $suggestion)
                        <a href="{{ route('stats.ai-assistant', ['q' => $suggestion]) }}">{{ $suggestion }}</a>
                    @endforeach
                </div>
            </div>
        @endunless

        <form method="GET" class="ask-bar">
            <input class="input" type="text" name="q" value="{{ $question }}" placeholder="اسأل حوات AI عن البيانات...">
            <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'send']) تحليل</button>
        </form>
    </div>
@endsection

@push('scripts')
@if ($insight && $insight['chart']['labels'])
@include('partials.chart-setup')
<script>
    new Chart(document.getElementById('insightChart'), {
        type: @json($insight['chart']['type']),
        data: {
            labels: @json($insight['chart']['labels']),
            datasets: [{
                label: @json($insight['chart']['unit']),
                data: @json($insight['chart']['values']),
                backgroundColor: hawatChart.accent,
                borderColor: hawatChart.accent
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true }, x: { grid: { display: false } } } }
    });
</script>
@endif
@endpush
