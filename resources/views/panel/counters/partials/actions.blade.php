{{--
    إيقاف العدّاد أو رفع إيقافه ونقله — مشترك بين صفحة العدّادين عند المدير
    العام وصفحة عدّادي الشركة. يمرَّر: $counter، $routes (suspend/reactivate/
    transfer)، $ports (موانئ النقل المسموحة)، $canReactivate.
--}}
<div style="display:flex;flex-wrap:wrap;gap:.35rem;justify-content:flex-end;align-items:flex-start">
    @if ($counter->isSuspended())
        @if ($canReactivate)
            <form method="POST" action="{{ route($routes['reactivate'], $counter) }}">
                @csrf
                <button type="submit" class="btn btn-outline">@include('partials.icon', ['name' => 'check-circle']) رفع الإيقاف</button>
            </form>
        @else
            <span class="card-sub">أوقفته الوزارة</span>
        @endif
    @else
        <details>
            <summary class="btn btn-outline" style="list-style:none;cursor:pointer">@include('partials.icon', ['name' => 'ban']) إيقاف</summary>
            <form method="POST" action="{{ route($routes['suspend'], $counter) }}" style="display:grid;gap:.4rem;margin-top:.5rem;min-width:220px">
                @csrf
                <label class="field"><span>السبب (اختياري)</span><textarea class="input" name="reason" rows="2" maxlength="1000"></textarea></label>
                <button type="submit" class="btn btn-outline" style="justify-self:start">تأكيد الإيقاف</button>
            </form>
        </details>
    @endif
    @if ($ports->where('id', '!=', $counter->port_id)->isNotEmpty())
        <details>
            <summary class="btn btn-outline" style="list-style:none;cursor:pointer">@include('partials.icon', ['name' => 'arrow-left-right']) نقل</summary>
            <form method="POST" action="{{ route($routes['transfer'], $counter) }}" style="display:grid;gap:.4rem;margin-top:.5rem;min-width:220px">
                @csrf
                <label class="field"><span>إلى ميناء *</span>
                    <select class="select" name="to_port_id" required>
                        <option value="">— اختر —</option>
                        @foreach ($ports->where('id', '!=', $counter->port_id) as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                    </select>
                </label>
                <label class="field"><span>السبب</span><input class="input" name="reason" maxlength="1000"></label>
                <button type="submit" class="btn btn-primary" style="justify-self:start">تأكيد النقل</button>
            </form>
        </details>
    @endif
</div>
