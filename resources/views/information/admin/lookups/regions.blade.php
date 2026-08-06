<x-information.reference-list type="regions" title="المناطق" :count="$records->count()">
    <x-slot:form>
        <div class="info-field">
            <label for="region-name">اسم المنطقة</label>
            <input id="region-name" name="name" value="{{ old('name') }}" maxlength="150" required
                   placeholder="مثال: منطقة مكة المكرمة" @error('name') aria-invalid="true" aria-describedby="region_name_error" @enderror>
            @error('name')<small id="region_name_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>
    </x-slot:form>

    <x-slot:table>
        <caption class="sr-only">المناطق المسجلة</caption>
        <thead>
            <tr>
                <th scope="col">المنطقة</th>
                <th scope="col">المحافظات</th>
                <th scope="col"><span class="sr-only">الإجراءات</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td><strong>{{ $record->name }}</strong></td>
                    <td>{{ $record->governorates_count }}</td>
                    <td><x-information.reference-actions type="regions" :record="$record->id" /></td>
                </tr>
            @empty
                <tr><td colspan="3" class="info-admin-empty">لا توجد مناطق مسجلة.</td></tr>
            @endforelse
        </tbody>
    </x-slot:table>
</x-information.reference-list>
