@extends('layouts.app')

@php
    $label = App\Support\Nav::label($routeName) ?? $routeName;
@endphp

@section('title', $label)

@section('content')
    <div class="pending-card">
        @include('partials.icon', ['name' => 'hammer'])
        <h3>{{ $label }}</h3>
        <p>هذه الصفحة قيد الترحيل إلى نسخة Laravel ضمن خارطة الطريق. القائمة الجانبية والمسار جاهزان، ويُستبدل هذا المحتوى بالصفحة الكاملة في مرحلتها المجدولة.</p>
    </div>
@endsection