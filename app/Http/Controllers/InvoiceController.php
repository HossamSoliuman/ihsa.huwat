<?php

namespace App\Http\Controllers;

use App\Models\DalalProfile;
use App\Models\Role;
use App\Models\Sale;
use Illuminate\View\View;

/**
 * الفاتورة المطبوعة — صفحة A4 مستقلة بلا قائمة، تُحفظ PDF من نافذة الطباعة.
 *
 * رابطها موقّع (Sale::invoiceUrl) لا خلف الدخول: يفتحه التطبيق في المتصفح
 * ليطبع الفاتورة أو يشاركها مع الزبون، ولا يُفتح بتغيير رقم الفاتورة.
 */
class InvoiceController extends Controller
{
    public function show(int $sale): View
    {
        $sale = Sale::with(['seller', 'customer', 'paymentMethod', 'paymentStatus', 'items.species', 'trip.boat'])->findOrFail($sale);

        $profile = $sale->seller?->hasAppRole(Role::DALAL) ? DalalProfile::forUser($sale->seller) : null;

        return view('invoices.show', ['sale' => $sale, 'profile' => $profile]);
    }
}
