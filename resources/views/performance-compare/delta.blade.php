@php
    /** فرق عن المتوسط: سهم صاعد أو هابط أو شرطة، مع إبراز الطرفين الأعلى والأقل. */
    $direction = $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat');
    $tone = ($low ?? false) ? 'down' : $direction;
    $icon = ['up' => 'trending-up', 'down' => 'trending-down', 'flat' => 'minus'][$direction];
@endphp
<span class="delta-pill {{ $tone }} {{ ($extreme ?? false) && ! ($low ?? false) ? 'extreme' : '' }}">
    @include('partials.icon', ['name' => $icon])
    {{ $delta > 0 ? '+' : '' }}{{ round($delta, 1) }}{{ $suffix ?? '' }}
</span>
