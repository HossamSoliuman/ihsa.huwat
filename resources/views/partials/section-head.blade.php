{{--
    ترويسة قسم داخل اللوحة: مربّع أيقونة، فعنوان، فخطٌّ يمتدّ إلى آخر السطر.
    الخطّ ليس زينة — هو ما يمنع بقاء فراغ بين العنوان وحافّة اللوحة.
--}}
<div class="section-head">
    <span class="ico">@include('partials.icon', ['name' => $icon])</span>
    <h2>{{ $title }}</h2>
    @if (!empty($note))
        <small>{{ $note }}</small>
    @endif
    <span class="line"></span>
</div>
