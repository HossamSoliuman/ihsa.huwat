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

        <label class="info-lookup-check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
            <span>نشط في النموذج</span>
        </label>
    </x-slot:form>

    <x-slot:table>
        <caption class="sr-only">المحافظات المسجلة</caption>
        <thead>
            <tr>
                <th scope="col">المحافظة</th>
                <th scope="col">المنطقة</th>
                <th scope="col">الموانئ</th>
                <th scope="col">الحالة</th>
                <th scope="col"><span class="sr-only">الإجراءات</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                @php($isLive = $record->is_active && $record->region->is_active)
                <tr @class(['is-retired' => ! $isLive])>
                    <td><strong>{{ $record->name }}</strong></td>
                    <td>{{ $record->region->name }}</td>
                    <td>{{ $record->ports_count }}</td>
                    <td>
                        <span class="info-status-chip" data-tone="{{ $isLive ? 'sea' : 'gold' }}">
                            <i aria-hidden="true"></i>{{ $isLive ? 'نشط' : ($record->is_active ? 'متوقف مع المنطقة' : 'متوقف') }}
                        </span>
                    </td>
                    <td><x-information.reference-actions type="governorates" :record="$record->id" :active="$record->is_active" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="info-admin-empty">لا توجد محافظات مسجلة.</td></tr>
            @endforelse
        </tbody>
    </x-slot:table>
</x-information.reference-list>
