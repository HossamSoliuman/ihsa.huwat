@php
    $badgeMap = $resource['badges'] ?? [];
    $columns = $resource['columns'];
    $readonly = ! empty($resource['readonly']);
    $updateTemplate = route('admin.resource.update', ['tab' => $activeTab, 'resource' => $activeResource, 'id' => '__ID__']);
    $fieldKeys = collect($fields)->pluck('key')->all();

    // حقول التاريخ تُحوَّل إلى Y-m-d لأن <input type="date"> لا يقبل صيغة ISO الكاملة.
    $editPayload = fn ($record) => collect($record->only($fieldKeys))
        ->map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value)
        ->all();
@endphp

@if (count($resources) > 1)
    <div class="subtabbar">
        @foreach ($resources as $key => $item)
            <a class="subtab @if ($key === $activeResource) is-active @endif"
               href="{{ route('admin.tab', ['tab' => $activeTab, 'resource' => $key]) }}">{{ $item['label'] }}</a>
        @endforeach
    </div>
@endif

<div class="section-head">
    <div>
        <h2>{{ $resource['title'] }}</h2>
        <p>{{ $resource['description'] }}</p>
    </div>
    @unless ($readonly)
        <button type="button" class="btn btn-primary" data-record-create>
            @include('admin.partials.icon', ['name' => 'plus'])
            إضافة
        </button>
    @endunless
</div>

<div class="table-wrap">
    @if ($records->isEmpty())
        <div class="empty-state">لا توجد سجلات بعد — استخدم زر "إضافة" أو شغّل البذور عبر php artisan migrate --seed</div>
    @else
        <table>
            <thead>
                <tr>
                    @foreach ($columns as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                    @unless ($readonly)
                        <th style="text-align:center;">إجراءات</th>
                    @endunless
                </tr>
            </thead>
            <tbody>
                @foreach ($records as $record)
                    <tr>
                        @foreach ($columns as $column => $label)
                            <td>
                                @include('admin.partials.cell', [
                                    'record' => $record,
                                    'column' => $column,
                                    'badgeMap' => $badgeMap,
                                ])
                            </td>
                        @endforeach
                        @unless ($readonly)
                            <td class="cell-actions">
                                <button type="button" class="btn-icon" title="تعديل"
                                        data-record-edit="{{ $record->id }}"
                                        data-record='@json($editPayload($record))'>
                                    @include('admin.partials.icon', ['name' => 'pencil'])
                                </button>
                                <form class="inline-form" method="POST"
                                      action="{{ route('admin.resource.destroy', ['tab' => $activeTab, 'resource' => $activeResource, 'id' => $record->id]) }}"
                                      onsubmit="return confirm('هل تريد حذف هذا السجل؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-icon is-danger" title="حذف">
                                        @include('admin.partials.icon', ['name' => 'trash'])
                                    </button>
                                </form>
                            </td>
                        @endunless
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@if ($records->hasPages())
    <div class="pager">
        <span>إجمالي السجلات: {{ number_format($records->total()) }}</span>
        {{ $records->onEachSide(1)->links('admin.partials.pagination') }}
    </div>
@else
    <div class="pager"><span>إجمالي السجلات: {{ number_format($records->total()) }}</span></div>
@endif

@unless ($readonly)
    <dialog class="modal" id="record-modal">
        <form method="POST" id="record-form"
              action="{{ route('admin.resource.store', ['tab' => $activeTab, 'resource' => $activeResource]) }}">
            @csrf
            <input type="hidden" name="_method" value="POST" id="record-method">

            <div class="modal-head">
                <h3 id="record-modal-title">إضافة سجل — {{ $resource['title'] }}</h3>
                <button type="button" class="btn-icon" data-record-close>
                    @include('admin.partials.icon', ['name' => 'close'])
                </button>
            </div>

            <div class="modal-body">
                <div class="form-grid">
                    @foreach ($fields as $field)
                        @include('admin.partials.field', ['field' => $field])
                    @endforeach
                </div>
            </div>

            <div class="modal-foot">
                <button type="submit" class="btn btn-primary">
                    @include('admin.partials.icon', ['name' => 'save'])
                    حفظ
                </button>
                <button type="button" class="btn btn-outline" data-record-close>إلغاء</button>
            </div>
        </form>
    </dialog>

    @push('scripts')
        <script>
            (function () {
                const modal = document.getElementById('record-modal');
                const form = document.getElementById('record-form');
                const method = document.getElementById('record-method');
                const title = document.getElementById('record-modal-title');
                const storeAction = form.getAttribute('action');
                const updateTemplate = @json($updateTemplate);
                const resourceTitle = @json($resource['title']);

                function resetForm() {
                    form.reset();
                    form.querySelectorAll('[type=checkbox]').forEach((box) => { box.checked = false; });
                }

                function fill(values) {
                    Object.entries(values).forEach(([key, value]) => {
                        const input = form.querySelector('[data-field="' + key + '"]');
                        if (!input) return;
                        if (input.type === 'checkbox') {
                            input.checked = Boolean(value);
                        } else {
                            input.value = value === null || value === undefined ? '' : value;
                        }
                    });
                }

                document.querySelector('[data-record-create]')?.addEventListener('click', function () {
                    resetForm();
                    form.setAttribute('action', storeAction);
                    method.value = 'POST';
                    title.textContent = 'إضافة سجل — ' + resourceTitle;
                    modal.showModal();
                });

                document.querySelectorAll('[data-record-edit]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        resetForm();
                        form.setAttribute('action', updateTemplate.replace('__ID__', button.dataset.recordEdit));
                        method.value = 'PUT';
                        title.textContent = 'تعديل سجل — ' + resourceTitle;
                        fill(JSON.parse(button.dataset.record));
                        modal.showModal();
                    });
                });

                document.querySelectorAll('[data-record-close]').forEach(function (button) {
                    button.addEventListener('click', () => modal.close());
                });
            })();
        </script>
    @endpush
@endunless