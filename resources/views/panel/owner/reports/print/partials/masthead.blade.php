{{--
    ترويسة التقرير المطبوع (report-masthead في hispa): هوية المالك يمينًا،
    وهوية المنصة يسارًا، ثم عنوان التقرير ووصفه في الوسط.
--}}
<div class="rmast">
    <div class="rmast-side">
        <div class="rmast-name">{{ $owner->name }}</div>
        <div class="rmast-facts">
            @if ($owner->phone)<span class="rf"><span class="rf-label">الهاتف</span><span class="rf-value"><bdi dir="ltr">{{ $owner->phone }}</bdi></span></span>@endif
            @if ($owner->email)<span class="rf"><span class="rf-label">البريد الإلكتروني</span><span class="rf-value">{{ $owner->email }}</span></span>@endif
        </div>
    </div>
    <div class="rmast-side rmast-end">
        <div class="rmast-name">{{ config('hawat.name') }}</div>
        <div class="rmast-facts"><span class="rf">{{ config('hawat.sector') }}</span></div>
    </div>
</div>

<div class="rtitle-wrap">
    <div class="rtitle">{{ $title ?? $meta['title'] }}</div>
    @if (! empty($subtitle))<div class="rsubtitle">{{ $subtitle }}</div>@endif
</div>
