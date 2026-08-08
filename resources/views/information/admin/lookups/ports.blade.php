<x-information.reference-list type="ports" title="الموانئ" :count="$records->count()">
    <x-slot:form>
        <x-information.region-governorate-select id-prefix="port" :regions="$regions" :governorates="$governorates" />

        <div class="info-field">
            <label for="port-name">اسم الميناء</label>
            <input id="port-name" name="name" value="{{ old('name') }}" maxlength="150" required
                   placeholder="مثال: ميناء جدة الإسلامي" @error('name') aria-invalid="true" aria-describedby="port_name_error" @enderror>
            @error('name')<small id="port_name_error" class="info-field-error">{{ $message }}</small>@enderror
        </div>

        <div class="info-field">
            <label for="port-location">اسم الموقع <small>اختياري</small></label>
            <input id="port-location" name="location_name" value="{{ old('location_name') }}" maxlength="190" placeholder="الوصف الجغرافي">
        </div>

        <div class="info-field">
            <label for="port-location-url">رابط الموقع <small>اختياري</small></label>
            <input id="port-location-url" type="url" name="location_url" value="{{ old('location_url') }}" maxlength="500" dir="ltr" placeholder="https://">
            @error('location_url')<small class="info-field-error">{{ $message }}</small>@enderror
        </div>

        <div class="info-field">
            <label for="port-latitude">خط العرض <small>اختياري</small></label>
            <input id="port-latitude" type="number" name="latitude" value="{{ old('latitude') }}" min="-90" max="90" step="0.000001" dir="ltr">
        </div>

        <div class="info-field">
            <label for="port-longitude">خط الطول <small>اختياري</small></label>
            <input id="port-longitude" type="number" name="longitude" value="{{ old('longitude') }}" min="-180" max="180" step="0.000001" dir="ltr">
        </div>

        <label class="info-lookup-check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
            <span>نشط في النموذج</span>
        </label>
    </x-slot:form>

    <x-slot:table>
        <caption class="sr-only">الموانئ المسجلة</caption>
        <thead>
            <tr>
                <th scope="col">الميناء</th>
                <th scope="col">المحافظة</th>
                <th scope="col">المنطقة</th>
                <th scope="col">الموقع</th>
                <th scope="col">الحالة</th>
                <th scope="col"><span class="sr-only">الإجراءات</span></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($records as $record)
                @php($isLive = $record->is_active && $record->governorate->is_active && $record->governorate->region->is_active)
                <tr @class(['is-retired' => ! $isLive])>
                    <td><strong>{{ $record->name }}</strong></td>
                    <td>{{ $record->governorate->name }}</td>
                    <td>{{ $record->governorate->region->name }}</td>
                    <td>{{ $record->location_name ?? '—' }}</td>
                    <td>
                        <span class="info-status-chip" data-tone="{{ $isLive ? 'sea' : 'gold' }}">
                            <i aria-hidden="true"></i>{{ $isLive ? 'نشط' : ($record->is_active ? 'متوقف مع المحافظة' : 'متوقف') }}
                        </span>
                    </td>
                    <td><x-information.reference-actions type="ports" :record="$record->id" :active="$record->is_active" /></td>
                </tr>
            @empty
                <tr><td colspan="6" class="info-admin-empty">لا توجد موانئ مسجلة.</td></tr>
            @endforelse
        </tbody>
    </x-slot:table>
</x-information.reference-list>
