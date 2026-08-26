{{--
    قائمة طلبات مشتركة بين "لوحة الموظف" و"مساحتي": الترويسة والحالة والبيانات
    التعريفية واحدة في اللوحتين، ولا يختلف إلا عنوان القائمة ولونها.

    $title, $icon, $tone, $items, $empty, $statusBadge
--}}
@php
    $accent = ['violet' => '#c4b5fd', 'amber' => '#fcd34d', 'sky' => '#7dd3fc', 'rose' => '#fda4af'][$tone] ?? '#7dd3fc';
@endphp
<section class="portal-group">
    <div class="head">
        <div class="badge-icon tone-{{ $tone }}">@include('partials.icon', ['name' => $icon])</div>
        <div>
            <h3>{{ $title }}</h3>
            <p>{{ $items->count() }} طلب</p>
        </div>
    </div>

    @forelse ($items as $item)
        <div class="alert-row" style="border-right-color:{{ $accent }}">
            <div class="a-icon" style="background:hsl(var(--primary) / .1);color:hsl(var(--primary))">
                @include('partials.icon', ['name' => $item->serviceType->icon])
            </div>
            <div style="min-width:0;flex:1">
                <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
                    <span style="font-family:monospace;font-size:.72rem;font-weight:700;color:hsl(var(--muted-foreground))">{{ $item->request_number }}</span>
                    <h3 style="font-size:.875rem;font-weight:700">{{ $item->serviceType->name }}</h3>
                    <span class="badge {{ $statusBadge[$item->status] ?? 'badge-info' }}">{{ $item->status }}</span>
                    @if ($item->priority === 'عاجلة')<span class="pill pill-rose">عاجلة</span>@endif
                </div>
                <p style="margin-top:.375rem;font-size:.85rem">
                    {{ $item->fisher_name }}@if ($item->national_id) <span style="color:hsl(var(--muted-foreground))">· هوية: {{ $item->national_id }}</span>@endif
                </p>
                <div class="alert-meta">
                    @if ($item->port)<span>الميناء: {{ $item->port->name }}</span>@endif
                    @if ($item->regionName())<span>المنطقة: {{ $item->regionName() }}</span>@endif
                    @if ($item->license_number)<span>الرخصة: {{ $item->license_number }}</span>@endif
                    @if ($item->fishingSeason)<span>الموسم: {{ $item->fishingSeason->name }}</span>@endif
                    <span>التقديم: {{ $item->submitted_date?->toDateString() ?? '—' }}</span>
                    @if ($item->assignedStaff)<span>المعالج: {{ $item->assignedStaff->name }}</span>@endif
                </div>
                @if ($item->new_license_number)
                    <p style="margin-top:.375rem;font-size:.78rem;font-weight:600;color:#047857">
                        الرخصة المقترحة: {{ $item->new_license_number }}@if ($item->new_license_expiry) (تنتهي {{ $item->new_license_expiry->toDateString() }})@endif
                    </p>
                @endif
            </div>
            <a href="{{ route('services.fisher-services', ['q' => $item->request_number]) }}" class="btn btn-outline" style="flex-shrink:0;padding:.35rem .6rem;font-size:.72rem">
                @include('partials.icon', ['name' => 'arrow-left']) فتح الطلب
            </a>
        </div>
    @empty
        <div class="pending-card">
            @include('partials.icon', ['name' => $icon])
            <h3>{{ $empty }}</h3>
            <p>تُبنى القائمة من صلاحيات الموظف وتخويله بالخدمات ونطاقه الجغرافي.</p>
        </div>
    @endforelse
</section>
