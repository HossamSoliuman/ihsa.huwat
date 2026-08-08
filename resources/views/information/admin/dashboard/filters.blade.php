<form class="info-dashboard-filters" method="get" action="{{ route('information.admin.dashboard') }}">
    <div class="info-field">
        <label for="dashboard-range">الفترة</label>
        <select id="dashboard-range" name="range">
            @foreach ($rangeOptions as $rangeValue => $rangeLabel)
                <option value="{{ $rangeValue }}" @selected($filters['range'] === (string) $rangeValue)>{{ $rangeLabel }}</option>
            @endforeach
        </select>
    </div>

    <x-information.region-governorate-select
        id-prefix="dashboard" :regions="$regions" :governorates="$governorates"
        :selected="$filters['governorate_id']" :selected-region="$filters['region_id']"
        region-name="region_id" governorate-filters="dashboard-port" :required="false" />

    <div class="info-field">
        <label for="dashboard-port">الميناء</label>
        <select id="dashboard-port" name="port_id" data-dashboard-port>
            <option value="">كل الموانئ</option>
            @foreach ($ports as $port)
                <option value="{{ $port->id }}" data-governorate="{{ $port->governorate_id }}" @selected($filters['port_id'] === $port->id)>{{ $port->name }}</option>
            @endforeach
        </select>
    </div>

    <button class="info-button info-button-primary" type="submit">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16M7 12h10m-7 7h4"></path></svg>
        <span>تطبيق الفلاتر</span>
    </button>
    @if ($filters['range'] !== '30' || $filters['port_id'] || $filters['governorate_id'] || $filters['region_id'])
        <a class="info-button info-button-ghost" href="{{ route('information.admin.dashboard') }}">إعادة الضبط</a>
    @endif
</form>
