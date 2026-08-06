@props(['type', 'record', 'active' => null])

<div class="info-lookup-actions">
    @unless (is_null($active))
        <form method="post" action="{{ route('information.admin.lookups.references.toggle', [$type, $record]) }}">
            @csrf
            @method('PATCH')
            <button class="info-lookup-action" type="submit">{{ $active ? 'تعطيل' : 'تفعيل' }}</button>
        </form>
    @endunless

    <form method="post" action="{{ route('information.admin.lookups.references.destroy', [$type, $record]) }}"
          data-confirm="سيُحذف هذا السجل نهائياً إذا لم يكن مرتبطاً ببيانات أخرى. هل تريد المتابعة؟">
        @csrf
        @method('DELETE')
        <button class="info-lookup-action is-danger" type="submit">حذف</button>
    </form>
</div>
