@php
    use App\Support\Nav;

    /*
     * قائمة "إدارة النظام" نفسها التي في /subadmin: تبويبات هذه البوابة ولوحات
     * القسم على النطاق الرئيسي في قائمة واحدة (config/hawat.php → nav_subadmin).
     * الأيقونات من مجموعة اللوحة لأن عناصر القسم منها.
     */
    $user = auth()->user();
@endphp

<aside class="sidebar" id="sidebar">
    {{-- الشعار في الشريط العلوي، فلم يبقَ في رأس القائمة إلا زرّ الإغلاق — ولا يظهر إلا دون 1024px. --}}
    <div class="sidebar-head">
        <button class="sidebar-close" onclick="toggleSidebar(false)" aria-label="إغلاق القائمة">
            @include('admin.partials.icon', ['name' => 'close'])
        </button>
    </div>

    <nav class="sidebar-nav">
        @foreach (Nav::sections(Nav::SUBADMIN) as $section)
            <div class="nav-section">
                <p class="nav-section-title">{{ $section['title'] }}</p>
                @foreach ($section['items'] as $item)
                    {{-- رئيسة البوابة تعرض التبويب الافتراضي، فيُعرف النشط من $activeTab لا من المسار. --}}
                    @php
                        $active = isset($item['tab']) && $item['tab'] === $activeTab;
                    @endphp
                    <a class="nav-link {{ $active ? 'is-active' : '' }}"
                       href="{{ Nav::url($item) }}"
                       @if ($active) aria-current="page" @endif>
                        @include('partials.icon', ['name' => $item['icon']])
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    {{-- ذيل القائمة: هويّة من دخل — وهي نفسها التي تُنسب إليها كتابات سجل العمليات. --}}
    <div class="sidebar-foot">
        <div class="user-chip">
            <div class="avatar">{{ mb_substr($user?->name ?? '؟', 0, 1) }}</div>
            <div class="meta">
                <p class="role">{{ $user?->name }}</p>
                <p class="sub">{{ $user?->role_label }}</p>
            </div>
        </div>
    </div>
</aside>
