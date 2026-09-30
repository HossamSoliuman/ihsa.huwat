@extends('layouts.app')

@section('title', $meta['title'])

@section('content')
    @include('panel.owner.reports.partials.head', ['print' => true])
    @include('panel.owner.reports.partials.filter', ['printable' => false])

    <div class="card">
        @include('panel.owner.reports.partials.month-statement')
        <small class="report-note">المعادلة: صافي الإيرادات − (مصاريف الرحلات + المصاريف العمومية + الإهلاك)، والرواتب الثابتة ضمن مصاريف الرحلات.</small>
    </div>
@endsection
