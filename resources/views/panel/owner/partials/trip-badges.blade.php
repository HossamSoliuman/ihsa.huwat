@php
    use App\Models\Trip;

    // لون الحالة: مجدولة رمادية، في البحر زرقاء، طور العد صفراء، معدودة خضراء، ملغاة حمراء.
    $tone = match ($trip->status) {
        Trip::AT_SEA => 'badge-info',
        Trip::RETURNED, Trip::AWAITING_COUNT, Trip::COUNTING => 'badge-warn',
        Trip::AWAITING_APPROVAL, Trip::APPROVED => 'badge-ok',
        Trip::CANCELLED => 'badge-danger',
        default => 'badge',
    };
    $saleTone = match ($trip->sale_status) {
        Trip::SALE_OPEN => 'badge-warn',
        Trip::SALE_DONE => 'badge-ok',
        default => 'badge',
    };
@endphp
<span class="badge {{ $tone }}" title="{{ $trip->status }}">{{ $trip->app_status }}</span>
@if ($trip->sale_status !== Trip::SALE_NOT_STARTED)
    <span class="badge {{ $saleTone }}">{{ $trip->sale_status }}</span>
@endif
