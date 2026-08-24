@extends('layouts.app')

@section('title', 'موظفو الإحصاء')

@section('content')
    <div class="page-header">
        <div class="lead">
            <div class="icon-wrap">@include('partials.icon', ['name' => 'users'])</div>
            <div>
                <h1>موظفو الإحصاء</h1>
                <p>فرق الإحصاء في الموانئ، ورديّاتهم، وعدد الرحلات المُحصاة</p>
            </div>
        </div>
    </div>

    <div class="stat-grid cols-5" style="margin-bottom:1.25rem">
        @include('partials.stat-card', ['label' => 'إجمالي الموظفين', 'value' => $stats['total'], 'icon' => 'users', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'نشطون', 'value' => $stats['active'], 'icon' => 'check-circle', 'tone' => 'success'])
        @include('partials.stat-card', ['label' => 'الرحلات المُحصاة', 'value' => number_format($stats['trips']), 'icon' => 'clipboard-check', 'tone' => 'primary'])
        @include('partials.stat-card', ['label' => 'متوسط لكل موظف', 'value' => number_format($stats['avg']), 'icon' => 'trending-up', 'tone' => 'info'])
        @include('partials.stat-card', ['label' => 'الموانئ المغطاة', 'value' => $stats['ports'], 'icon' => 'anchor', 'tone' => 'primary'])
    </div>

    <form method="GET" class="filter-bar" style="margin-bottom:1.25rem">
        <label class="field"><span>الميناء</span>
            <select class="select" name="port" onchange="this.form.submit()">
                <option value="">كل الموانئ</option>
                @foreach ($ports as $port)<option value="{{ $port->id }}" @selected(request('port') == $port->id)>{{ $port->name }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الوردية</span>
            <select class="select" name="shift" onchange="this.form.submit()">
                <option value="">كل الورديات</option>
                @foreach ($shifts as $shift)<option value="{{ $shift }}" @selected(request('shift') === $shift)>{{ $shift }}</option>@endforeach
            </select>
        </label>
        <label class="field"><span>الحالة</span>
            <select class="select" name="status" onchange="this.form.submit()">
                <option value="">كل الحالات</option>
                @foreach (['نشط', 'إجازة', 'موقوف'] as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>@endforeach
            </select>
        </label>
        <a href="{{ route('stats.statistics-officers') }}" class="btn btn-outline">إعادة تعيين</a>
    </form>

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr><th>الموظف</th><th>الرقم الوظيفي</th><th>الميناء</th><th>الوردية</th><th>الرحلات المُحصاة</th><th>الحالة</th></tr>
            </thead>
            <tbody>
                @forelse ($officers as $officer)
                    <tr>
                        <td style="font-weight:600">{{ $officer->name }}</td>
                        <td style="font-family:monospace;font-size:.72rem">{{ $officer->employee_number }}</td>
                        <td>{{ $officer->port?->name ?? '—' }}</td>
                        <td>{{ $officer->shift }}</td>
                        <td style="font-weight:600">{{ number_format($officer->trips_counted) }}</td>
                        <td><span class="badge {{ $officer->status === 'نشط' ? 'badge-ok' : 'badge-warn' }}">{{ $officer->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="padding:2rem;text-align:center;color:hsl(var(--muted-foreground))">لا يوجد موظفون مطابقون</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection