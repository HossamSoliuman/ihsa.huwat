<x-information.reference-list type="regions" title="المناطق" :count="$records->count()">
    <x-slot:form>
        <div class="info-field">
            <label for="region-name">اسم المنطقة</label>
            <input id="region-name" name="name" value="{{ old('name') }}" maxlength="150" required
                   placeholder="مثال: منطقة مكة المكرمة" @error('name') aria-invalid="true" aria-describedby="region_name_error" @enderror>
            @error('name')<small id="region_name_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        <label class="info-lookup-check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
            <span>نشط في النموذج</span>
        </label>
    </x-slot:form>

    <x-slot:table>
        <caption class="sr-only">المناطق المسجلة</caption>
        <thead>
            <tr>
                <th scope="col">المنطقة</th>
                <th scope="col">المحافظات</th>
                <th scope="col">الحالة</th>
                <th scope="col"><span class="sr-only">الإجراءات</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr @class(['is-retired' => ! $record->is_active])>
                    <td><strong>{{ $record->name }}</strong></td>
                    <td>{{ $record->governorates_count }}</td>
                    <td>
                        <span class="info-status-chip" data-tone="{{ $record->is_active ? 'sea' : 'gold' }}">
                            <i aria-hidden="true"></i>{{ $record->is_active ? 'نشط' : 'متوقف' }}
                        </span>
                    </td>
                    <td><x-information.reference-actions type="regions" :record="$record->id" :active="$record->is_active" /></td>
                </tr>
            @empty
                <tr><td colspan="4" class="info-admin-empty">لا توجد مناطق مسجلة.</td></tr>
            @endforelse
        </tbody>
    </x-slot:table>
</x-information.reference-list>
