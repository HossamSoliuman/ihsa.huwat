@props(['list', 'title', 'options'])

@php
    /** `old()` is shared by every list on the page, so only the list that failed repopulates. */
    $isRejected = old('list') === $list;
@endphp

<section class="info-form-card info-admin-panel info-lookup-panel" aria-labelledby="list-{{ $list }}">
    <div class="info-admin-panel-head">
        <h2 id="list-{{ $list }}">{{ $title }}</h2>
        <small class="info-lookup-count">{{ $options->where('is_active', true)->count() }} من {{ $options->count() }} فعّال</small>
    </div>

    <form class="info-lookup-form" method="post" action="{{ route('information.admin.lookups.options.store', $list) }}">
        @csrf
        <input type="hidden" name="list" value="{{ $list }}">

        <div class="info-field">
            <label for="{{ $list }}-name">اسم الخيار</label>
            <input id="{{ $list }}-name" name="name" value="{{ $isRejected ? old('name') : '' }}"
                   maxlength="150" required placeholder="كما يظهر للمتقدم">
        </div>

        <button class="info-button info-button-primary" type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"></path></svg>
            <span>إضافة</span>
        </button>
    </form>

    <div class="info-admin-table-scroll">
        <table class="info-admin-table info-lookup-table">
            <caption class="sr-only">خيارات {{ $title }}</caption>
            <thead>
                <tr>
                    <th scope="col">الاسم</th>
                    <th scope="col">الترتيب</th>
                    <th scope="col">الحالة</th>
                    <th scope="col"><span class="sr-only">الإجراءات</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($options as $option)
                    @php $formId = $list.'-'.$option->id; @endphp
                    <tr @class(['is-retired' => ! $option->is_active])>
                        <td>
                            <label class="sr-only" for="{{ $formId }}-name">اسم الخيار</label>
                            <input id="{{ $formId }}-name" class="info-lookup-input" form="{{ $formId }}"
                                   name="name" value="{{ $option->name }}" maxlength="150" required>
                        </td>
                        <td>
                            <label class="sr-only" for="{{ $formId }}-order">الترتيب</label>
                            <input id="{{ $formId }}-order" class="info-lookup-input is-narrow" form="{{ $formId }}"
                                   type="number" name="sort_order" value="{{ $option->sort_order }}" min="0" max="9999" required>
                        </td>
                        <td>
                            <span class="info-status-chip" data-tone="{{ $option->is_active ? 'sea' : 'gold' }}">
                                <i aria-hidden="true"></i>{{ $option->is_active ? 'فعّال' : 'متوقف' }}
                            </span>
                        </td>
                        <td>
                            <div class="info-lookup-actions">
                                {{-- The row's inputs live in other cells and reach this form by id. --}}
                                <form id="{{ $formId }}" method="post" action="{{ route('information.admin.lookups.options.update', [$list, $option]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="info-lookup-action" type="submit">حفظ</button>
                                </form>

                                <form method="post" action="{{ route('information.admin.lookups.options.toggle', [$list, $option]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="info-lookup-action" type="submit">{{ $option->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                                </form>

                                @unless ($option->is_active)
                                    <form method="post" action="{{ route('information.admin.lookups.options.destroy', [$list, $option]) }}"
                                          data-confirm="سيختفي هذا الخيار نهائياً، وستظهر الطلبات القديمة المرتبطة به بالرمز المخزَّن بدل الاسم. هل تريد المتابعة؟">
                                        @csrf
                                        @method('DELETE')
                                        <button class="info-lookup-action is-danger" type="submit">حذف</button>
                                    </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="info-admin-empty">لا توجد خيارات في هذه القائمة.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
