<x-information.reference-list type="cities" title="المدن والمراكز" :count="$records->count()">
    <x-slot:form>
        <div class="info-field">
            <label for="city-region">المنطقة</label>
            <select id="city-region" data-lookup-region-filter="city-governorate">
                <option value="">كل المناطق</option>
                @foreach ($regions as $region)
                    <option value="{{ $region->id }}">{{ $region->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="info-field">
            <label for="city-governorate">المحافظة</label>
            <select id="city-governorate" name="governorate_id" required
                    @error('governorate_id') aria-invalid="true" aria-describedby="city_governorate_error" @enderror>
                <option value="">اختر المحافظة</option>
                @foreach ($governorates as $governorate)
                    <option value="{{ $governorate->id }}" data-region="{{ $governorate->region_id }}"
                            @selected((string) old('governorate_id') === (string) $governorate->id)>{{ $governorate->name }}</option>
                @endforeach
            </select>
            @error('governorate_id')<small id="city_governorate_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        <div class="info-field">
            <label for="city-name">اسم المدينة / المركز</label>
            <input id="city-name" name="name" value="{{ old('name') }}" maxlength="150" required
                   placeholder="مثال: ثول" @error('name') aria-invalid="true" aria-describedby="city_name_error" @enderror>
            @error('name')<small id="city_name_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>
    </x-slot:form>

    <x-slot:table>
        <caption class="sr-only">المدن والمراكز المسجلة</caption>
        <thead>
            <tr>
                <th scope="col">المدينة / المركز</th>
                <th scope="col">المحافظة</th>
                <th scope="col">المنطقة</th>
                <th scope="col"><span class="sr-only">الإجراءات</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                <tr>
                    <td><strong>{{ $record->name }}</strong></td>
                    <td>{{ $record->governorate->name }}</td>
                    <td>{{ $record->governorate->region->name }}</td>
                    <td><x-information.reference-actions type="cities" :record="$record->id" /></td>
                </tr>
            @empty
                <tr><td colspan="4" class="info-admin-empty">لا توجد مدن مسجلة بعد.</td></tr>
            @endforelse
        </tbody>
    </x-slot:table>
</x-information.reference-list>
