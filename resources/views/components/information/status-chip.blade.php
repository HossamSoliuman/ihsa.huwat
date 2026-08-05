@props(['status'])

@php
    $tones = [
        'submitted' => 'sea',
        'under_review' => 'blue',
        'needs_edit' => 'gold',
        'approved' => 'approved',
        'rejected' => 'danger',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'info-status-chip']) }} data-tone="{{ $tones[$status] ?? 'neutral' }}">
    <i aria-hidden="true"></i>{{ \App\Models\InformationSubmission::STATUS_LABELS[$status] ?? $status }}
</span>
