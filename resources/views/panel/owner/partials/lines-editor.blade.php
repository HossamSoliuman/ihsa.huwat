{{--
    محرّر سطور البيع/الإرسال: صنف من المتاح في الرحلة، ووزن لا يتجاوزه، وسعر
    الكيلو في البيع وحده. المجموع يُحسب في المتصفح ويُعاد حسابه في الخادم.
    المتغيرات: $available (سطور الرحلة بمتاحها)، $priced (بيع أم إرسال).
--}}
<table class="data-table lines-table" style="margin-bottom:.75rem">
    <thead>
        <tr>
            <th>نوع السمك</th>
            <th style="width:8rem">الوزن (كجم)</th>
            @if ($priced)<th style="width:8rem">سعر الكيلو</th><th style="width:8rem;text-align:left">الإجمالي</th>@endif
            <th style="width:2.5rem"></th>
        </tr>
    </thead>
    <tbody id="lines"></tbody>
    <tfoot>
        <tr>
            <td colspan="{{ $priced ? 3 : 1 }}" style="text-align:left;font-size:.78rem">
                <button type="button" class="btn btn-outline" onclick="addLine()">@include('partials.icon', ['name' => 'plus']) إضافة سطر</button>
            </td>
            <td class="num" style="font-weight:700;text-align:left" id="linesTotal">0</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<script>
    const availableLines = @json($available);
    const priced = @json($priced);
    let lineIndex = 0;

    function lineOptions(selected) {
        return '<option value="">— اختر —</option>' + availableLines
            .filter(l => l.available_kg > 0 || String(l.species_id) === String(selected))
            .map(l => `<option value="${l.species_id}" data-max="${l.available_kg}" ${String(selected) === String(l.species_id) ? 'selected' : ''}>${l.species} — المتاح ${l.available_kg} كجم</option>`)
            .join('');
    }

    function addLine(line = {}) {
        const i = lineIndex++;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><select class="select" name="items[${i}][species_id]" required onchange="syncMax(this)">${lineOptions(line.species_id)}</select></td>
            <td><input class="input num" type="number" step="0.01" min="0.01" name="items[${i}][weight_kg]" value="${line.weight_kg ?? ''}" dir="ltr" required oninput="sumLines()"></td>
            ${priced ? `<td><input class="input num" type="number" step="0.01" min="0.01" name="items[${i}][price_per_kg]" value="${line.price_per_kg ?? ''}" dir="ltr" required oninput="sumLines()"></td><td class="num line-total" style="text-align:left">0</td>` : ''}
            <td><button type="button" class="icon-action danger" onclick="this.closest('tr').remove(); sumLines()">×</button></td>`;
        document.getElementById('lines').appendChild(tr);
        syncMax(tr.querySelector('select'));
        sumLines();
    }

    // الحد الأعلى للوزن = المتاح من الصنف المختار.
    function syncMax(select) {
        const max = select.options[select.selectedIndex]?.dataset.max;
        const weight = select.closest('tr').querySelector('input[name$="[weight_kg]"]');
        if (max) weight.max = max; else weight.removeAttribute('max');
    }

    function sumLines() {
        let kg = 0, money = 0;
        document.querySelectorAll('#lines tr').forEach(tr => {
            const w = parseFloat(tr.querySelector('input[name$="[weight_kg]"]')?.value) || 0;
            kg += w;
            if (priced) {
                const p = parseFloat(tr.querySelector('input[name$="[price_per_kg]"]')?.value) || 0;
                const t = w * p;
                money += t;
                tr.querySelector('.line-total').textContent = t.toFixed(2);
            }
        });
        document.getElementById('linesTotal').textContent = priced ? money.toFixed(2) + ' ر.س' : kg.toFixed(1) + ' كجم';
        if (typeof onLinesTotal === 'function') onLinesTotal(money, kg);
    }

    @if (old('items'))
        @foreach (old('items') as $line) addLine(@json($line)); @endforeach
    @else
        addLine();
    @endif
</script>
