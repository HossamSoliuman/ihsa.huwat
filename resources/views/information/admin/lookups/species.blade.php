<x-information.reference-list type="species" title="أنواع الأسماك" :count="$records->count()">
    <x-slot:form>
        <div class="info-field">
            <label for="species-name">اسم النوع</label>
            <input id="species-name" name="name_ar" value="{{ old('name_ar') }}" maxlength="150" required
                   placeholder="مثال: هامور" @error('name_ar') aria-invalid="true" aria-describedby="species_name_error" @enderror>
            @error('name_ar')<small id="species_name_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>
    </x-slot:form>

    <x-slot:table>
        <caption class="sr-only">أنواع الأسماك المسجلة</caption>
        <thead>
            <tr>
                <th scope="col">الترميز</th>
                <th scope="col">النوع</th>
                <th scope="col">الاسم العلمي</th>
                <th scope="col">العائلة</th>
                <th scope="col">سجلات المصيد</th>
                <th scope="col"><span class="sr-only">الإجراءات</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td>{{ $record->code ?? '—' }}</td>
                    <td><strong>{{ $record->name_ar }}</strong></td>
                    <td>{{ $record->scientific_name ?? '—' }}</td>
                    <td>{{ $record->family?->scientific_name ?? '—' }}</td>
                    <td>{{ $record->catch_details_count }}</td>
                    <td><x-information.reference-actions type="species" :record="$record->id" /></td>
                </tr>
            @empty
                <tr><td colspan="6" class="info-admin-empty">لا توجد أنواع مسجلة.</td></tr>
            @endforelse
        </tbody>
    </x-slot:table>
</x-information.reference-list>
