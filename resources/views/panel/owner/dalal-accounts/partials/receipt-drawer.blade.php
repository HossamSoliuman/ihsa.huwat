{{--
    درج "تسجيل استلام" من دلال — مشترك بين قائمة الحسابات وكشف الدلال.
    openReceipt({id, name, balance}) يفتحه على الدلال ويضع المستحق حدًّا.
--}}
<div class="drawer-overlay" id="receiptDrawer-overlay" onclick="toggleDrawer('receiptDrawer', false)"></div>
<div class="drawer" id="receiptDrawer">
    <div class="drawer-head">
        <h3 id="receiptTitle">تسجيل استلام من دلال</h3>
        <button type="button" class="icon-action" onclick="toggleDrawer('receiptDrawer', false)">@include('partials.icon', ['name' => 'x'])</button>
    </div>
    <form method="POST" id="receiptForm" action="" class="drawer-body" autocomplete="off">
        @csrf
        {{-- يعيد فتح الدرج على الدلال نفسه إن رُفض المبلغ. --}}
        <input type="hidden" name="receipt_dalal">
        <input type="hidden" name="receipt_dalal_name">
        <input type="hidden" name="receipt_balance">
        <p id="receiptHint" style="font-size:.76rem;color:hsl(var(--muted-foreground));margin-bottom:.75rem"></p>
        <div class="form-grid cols-2">
            <label class="field"><span>المبلغ (ر.س) *</span><input class="input num" type="number" step="0.01" min="0.01" name="amount" dir="ltr" required value="{{ old('amount') }}"></label>
            <label class="field"><span>تاريخ الاستلام</span><input class="input" type="date" name="paid_at" dir="ltr" max="{{ now()->toDateString() }}" value="{{ old('paid_at', now()->toDateString()) }}"></label>
            <label class="field"><span>طريقة الدفع</span>
                <select class="select" name="payment_method_id">
                    <option value="">—</option>
                    @foreach ($paymentMethods as $m)<option value="{{ $m->id }}" @selected((string) old('payment_method_id') === (string) $m->id)>{{ $m->name }}</option>@endforeach
                </select>
            </label>
            <label class="field"><span>رقم الحوالة / السند</span><input class="input num" name="reference" dir="ltr" maxlength="100" value="{{ old('reference') }}"></label>
            <label class="field wide"><span>ملاحظات</span><textarea class="input" name="notes" rows="2" maxlength="500">{{ old('notes') }}</textarea></label>
        </div>
        <p style="font-size:.72rem;color:hsl(var(--muted-foreground));margin-top:.5rem">يُبلَّغ الدلال بالاستلام ويظهر في حسابك عنده. ما يسجّله الدلال بنفسه لا تكرّره هنا.</p>
        <div style="display:flex;justify-content:flex-end;gap:.5rem;padding-top:.5rem">
            <button type="button" class="btn btn-outline" onclick="toggleDrawer('receiptDrawer', false)">إلغاء</button>
            <button type="submit" class="btn btn-primary">@include('partials.icon', ['name' => 'save']) حفظ الاستلام</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    const receiptUrl = @json(route('panel.owner.dalal-accounts.receipts.store', ['dalal' => '__ID__']));

    function openReceipt(dalal) {
        const form = document.getElementById('receiptForm');
        form.action = receiptUrl.replace('__ID__', dalal.id);
        form.receipt_dalal.value = dalal.id;
        form.receipt_dalal_name.value = dalal.name;
        form.receipt_balance.value = dalal.balance;
        form.amount.max = dalal.balance;
        if (!form.amount.value) form.amount.value = dalal.balance;
        document.getElementById('receiptTitle').textContent = 'تسجيل استلام من ' + dalal.name;
        document.getElementById('receiptHint').textContent = 'المستحق لك عنده ' + Number(dalal.balance).toLocaleString('en-US', { minimumFractionDigits: 2 }) + ' ر.س — الاستلام لا يتجاوزه.';
        toggleDrawer('receiptDrawer', true);
    }

    @if ($errors->has('amount') && old('receipt_dalal'))
        openReceipt(@json(['id' => (int) old('receipt_dalal'), 'name' => (string) old('receipt_dalal_name'), 'balance' => (float) old('receipt_balance')]));
    @endif
</script>
@endpush
