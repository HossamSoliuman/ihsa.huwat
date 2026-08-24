@php $entries = collect($items)->values(); @endphp

@if ($entries->isEmpty())
    <p style="padding:24px;text-align:center;font-size:10px;color:#94a3b8">لا توجد بيانات لهذه السنة</p>
@else
    <div class="share">
        @foreach ($entries as $entry)
            <span style="width:{{ $entry['share'] }}%;background:{{ $palette[$loop->index % count($palette)] }}"></span>
        @endforeach
    </div>
    <div class="legend">
        @foreach ($entries as $entry)
            <div class="row">
                <span><i style="background:{{ $palette[$loop->index % count($palette)] }}"></i>{{ $entry['label'] }}</span>
                <b>{{ $entry['value'] }}</b>
            </div>
        @endforeach
    </div>
@endif
