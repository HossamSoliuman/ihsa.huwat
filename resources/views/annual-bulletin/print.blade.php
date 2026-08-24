@extends('layouts.print')

@section('title', 'النشرة السنوية للمصايد البحرية '.$year)

@push('print-styles')
    @include('annual-bulletin.styles')
    <style>
        /* نسخة الطباعة مستند وحده: لا حشو صفحة ولا خلفية حول الورقة. */
        body { padding: 0; }
    </style>
@endpush

@section('content')
    @include('annual-bulletin.pages')
@endsection
