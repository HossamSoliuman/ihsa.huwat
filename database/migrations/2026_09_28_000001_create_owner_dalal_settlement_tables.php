<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * O5 — تسوية المالك مع الدلال.
 *
 * - `dalal_invoice_reviews`: فاتورة الدلال من جهة المالك = سطور مصيده في بيعٍ
 *   واحد، فالمراجعة سطر لكل (بيع × مالك): قيد المراجعة → مقبولة أو مرفوضة
 *   بسبب، والدلال يردّ على المرفوضة فتعود قيد المراجعة. الأرقام لا تُنسخ —
 *   تُجمع من `sale_items` دائمًا.
 * - `dalal_payouts.reference`: رقم الحوالة أو السند — المالك أيضًا يسجّل ما
 *   استلمه من الدلال، و`user_id` يبقى "من سجّل".
 *
 * مبيعات الدلال السابقة تُبذر مراجعاتها قيد المراجعة.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dalal_invoice_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dalal_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('dalal_reply')->nullable();
            $table->dateTime('replied_at')->nullable();
            $table->timestamps();

            $table->unique(['sale_id', 'owner_id']);
            $table->index(['owner_id', 'status']);
        });

        Schema::table('dalal_payouts', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('paid_at');
        });

        $rows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereNotNull('sale_items.owner_id')
            ->whereColumn('sale_items.owner_id', '!=', 'sales.seller_id')
            ->select('sale_items.sale_id', 'sale_items.owner_id', 'sales.seller_id', 'sales.sold_at')
            ->distinct()
            ->get();

        foreach ($rows->chunk(200) as $chunk) {
            DB::table('dalal_invoice_reviews')->insert($chunk->map(fn ($row) => [
                'sale_id' => $row->sale_id,
                'owner_id' => $row->owner_id,
                'dalal_id' => $row->seller_id,
                'status' => 'pending',
                'created_at' => $row->sold_at ?? now(),
                'updated_at' => $row->sold_at ?? now(),
            ])->all());
        }
    }

    public function down(): void
    {
        Schema::table('dalal_payouts', function (Blueprint $table) {
            $table->dropColumn('reference');
        });

        Schema::dropIfExists('dalal_invoice_reviews');
    }
};
