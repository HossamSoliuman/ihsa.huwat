<x-information.reference-list type="governorates" title="المحافظات" :count="$records->count()">
    <x-slot:form>
        <div class="info-field">
            <label for="governorate-region">المنطقة</label>
            <select id="governorate-region" name="region_id" required
                    @error('region_id') aria-invalid="true" aria-describedby="governorate_region_error" @enderror>
                <option value="">اختر المنطقة</option>
                @foreach ($regions as $region)
                    <option value="{{ $region->id }}" @selected((string) old('region_id') === (string) $region->id)>{{ $region->name }}</option>
                @endforeach
            </select>
            @error('region_id')<small id="governorate_region_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        <div class="info-field">
            <label for="governorate-name">اسم المحافظة</label>
            <input id="governorate-name" name="name" value="{{ old('name') }}" maxlength="150" required
                   placeholder="مثال: جدة" @error('name') aria-invalid="true" aria-describedby="governorate_name_error" @enderror>
            @error('name')<small id="governorate_name_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>
    </x-slot:form>

    <x-slot:table>
        <caption class="sr-only">المحافظات المسجلة</caption>
        <thead>
            <tr>
                <th scope="col">المحافظة</th>
                <th scope="col">المنطقة</th>
                <th scope="col">المدن</th>
                <th scope="col">الموانئ</th>
                <th scope="col"><span class="sr-only">الإجراءات</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td><strong>{{ $record->name }}</strong></td>
                    <td>{{ $record->region->name }}</td>
                    <td>{{ $record->cities_count }}</td>
                    <td>{{ $record->ports_count }}</td>
                    <td><x-information.reference-actions type="governorates" :record="$record->id" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="info-admin-empty">لا توجد محافظات مسجلة.</td></tr>
            @endforelse
        </tbody>
    </x-slot:table>
</x-information.reference-list>
