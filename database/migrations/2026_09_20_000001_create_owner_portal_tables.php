<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * المرحلة 1 — بوابة المالك: الأسطول والطاقم والموظفون والعملاء والموردون
 * والرحلات بدورتها التجارية، والمبيعات والإرسال للدلال ودفتر المخزون.
 *
 * كل قائمة اختيار جدول مرجعي مستقل (لا جدول مفاتيح/قيم مشترك)، والجداول
 * القديمة (boats, fishers, trips, catch_records) تُمدَّد بمفاتيح لا تُستبدل:
 * أعمدة النصوص القديمة (owner, captain, captain_name…) تبقى لأن صفحات
 * الوزارة تقرؤها، وتُملأ من العلاقات عند الحفظ.
 */
return new class extends Migration
{
    /**
     * القوائم المرجعية كلها بالبنية نفسها: اسم عربي فريد، اسم إنجليزي، فعّال، ترتيب.
     */
    private const LOOKUPS = [
        'boat_categories',
        'boat_types',
        'maintenance_types',
        'trip_types',
        'fisher_roles',
        'id_types',
        'job_titles',
        'payment_methods',
        'payment_statuses',
        'customer_types',
        'stock_movement_types',
    ];

    public function up(): void
    {
        foreach (self::LOOKUPS as $lookup) {
            Schema::create($lookup, function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('name_en')->nullable();
                $table->boolean('active')->default(true);
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('boats', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('port_id')->constrained('users')->nullOnDelete();
            $table->foreignId('captain_id')->nullable()->after('owner_id')->constrained('users')->nullOnDelete();
            $table->foreignId('boat_category_id')->nullable()->after('captain_id')->constrained()->nullOnDelete();
            $table->foreignId('boat_type_id')->nullable()->after('boat_category_id')->constrained()->nullOnDelete();
            $table->string('name_en')->nullable()->after('name');
            $table->decimal('width_m', 6, 2)->nullable()->after('length_m');
            $table->string('color')->nullable()->after('width_m');
            $table->string('hull_number')->nullable()->after('color');
            $table->string('body_type')->nullable()->after('hull_number');
            $table->string('engine_type')->nullable()->after('body_type');
            $table->string('engine_power')->nullable()->after('engine_type');
            $table->string('engine_status')->nullable()->after('engine_power');
            $table->string('call_sign')->nullable()->after('engine_status');
            $table->string('serial_number')->nullable()->after('call_sign');
            $table->unsignedInteger('capacity')->nullable()->after('serial_number');
            $table->string('license_process')->nullable()->after('license_expiry');
            $table->string('license_area')->nullable()->after('license_process');
            $table->date('license_date')->nullable()->after('license_area');
            $table->date('next_inspection_date')->nullable()->after('license_date');
        });

        Schema::create('boat_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('boat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_type_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('technician')->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('معلقة');
            $table->timestamps();
        });

        Schema::table('fishers', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('boat_id')->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->after('owner_id')->constrained()->nullOnDelete();
            $table->foreignId('fisher_role_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('nationality')->nullable()->after('name');
            $table->foreignId('id_type_id')->nullable()->after('nationality')->constrained()->nullOnDelete();
            $table->string('email')->nullable()->after('phone');
        });

        Schema::create('owner_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_title_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('nationality')->nullable();
            $table->string('id_number')->nullable();
            $table->string('status')->default('نشط');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            // صاحب القائمة: مالك أو دلال — لكلٍّ عملاؤه.
            $table->foreignId('account_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('governorate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('نشط');
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('نشط');
            $table->timestamps();
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->after('boat_id')->constrained('users')->nullOnDelete();
            $table->foreignId('captain_id')->nullable()->after('owner_id')->constrained('users')->nullOnDelete();
            $table->foreignId('counter_id')->nullable()->after('captain_id')->constrained('users')->nullOnDelete();
            $table->foreignId('trip_type_id')->nullable()->after('counter_id')->constrained()->nullOnDelete();
            $table->foreignId('return_port_id')->nullable()->after('departure_port_id')->constrained('ports')->nullOnDelete();
            $table->unsignedSmallInteger('planned_days')->nullable()->after('duration_hours');
            $table->string('license_number')->nullable()->after('gear_type');
            $table->dateTime('started_at')->nullable()->after('return_time');
            $table->dateTime('catch_submitted_at')->nullable()->after('started_at');
            $table->dateTime('received_at')->nullable()->after('catch_submitted_at');
            $table->dateTime('counted_at')->nullable()->after('received_at');
            $table->dateTime('cancelled_at')->nullable()->after('counted_at');
            $table->text('cancel_reason')->nullable()->after('cancelled_at');
            $table->string('sale_status')->default('لم يبدأ')->after('status');
        });

        Schema::table('catch_records', function (Blueprint $table) {
            $table->decimal('captain_kg', 12, 2)->nullable()->after('quantity_kg');
            $table->decimal('counted_kg', 12, 2)->nullable()->after('captain_kg');
            $table->text('captain_notes')->nullable()->after('total_value');
            $table->text('counter_notes')->nullable()->after('captain_notes');
            $table->boolean('verified')->default(false)->after('counter_notes');
            $table->foreignId('added_by')->nullable()->after('verified')->constrained('users')->nullOnDelete();
            $table->foreignId('corrected_by')->nullable()->after('added_by')->constrained('users')->nullOnDelete();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            // البائع مالك أو دلال؛ الرحلة تُذكر حين يُباع مصيدها مباشرة.
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_status_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('commission_pct', 5, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('wage_pct', 5, 2)->default(0);
            $table->decimal('wage_amount', 12, 2)->default(0);
            $table->decimal('owner_net', 12, 2)->default(0);
            $table->string('status')->default('مكتمل');
            $table->dateTime('sold_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('species_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight_kg', 12, 2);
            $table->decimal('price_per_kg', 10, 2);
            $table->decimal('total', 12, 2);
            $table->timestamps();
        });

        Schema::create('consignments', function (Blueprint $table) {
            $table->id();
            $table->string('consignment_number')->unique();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dalal_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('boat_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_kg', 12, 2)->default(0);
            $table->string('status')->default('مرسلة');
            $table->dateTime('sent_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('consignment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('species_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight_kg', 12, 2);
            $table->timestamps();
        });

        /*
         * دفتر المخزون: سطر لكل حركة بوزن موجب (دخول) أو سالب (خروج)، والحائز
         * مالك أو دلال. "الوزن المتاح" في التطبيق = مجموع حركات الحائز على
         * الصنف في الرحلة؛ والرصيد بعد الحركة يُحفظ لتقرير حركات الصنف.
         */
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holder_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('species_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stock_movement_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('weight_kg', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->nullableMorphs('reference');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['holder_id', 'species_id', 'trip_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('consignment_items');
        Schema::dropIfExists('consignments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');

        Schema::table('catch_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('added_by');
            $table->dropConstrainedForeignId('corrected_by');
            $table->dropColumn(['captain_kg', 'counted_kg', 'captain_notes', 'counter_notes', 'verified']);
        });

        Schema::table('trips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropConstrainedForeignId('captain_id');
            $table->dropConstrainedForeignId('counter_id');
            $table->dropConstrainedForeignId('trip_type_id');
            $table->dropConstrainedForeignId('return_port_id');
            $table->dropColumn(['planned_days', 'license_number', 'started_at', 'catch_submitted_at', 'received_at', 'counted_at', 'cancelled_at', 'cancel_reason', 'sale_status']);
        });

        Schema::dropIfExists('vendors');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('owner_employees');

        Schema::table('fishers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropConstrainedForeignId('user_id');
            $table->dropConstrainedForeignId('fisher_role_id');
            $table->dropConstrainedForeignId('id_type_id');
            $table->dropColumn(['nationality', 'email']);
        });

        Schema::dropIfExists('boat_maintenances');

        Schema::table('boats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_id');
            $table->dropConstrainedForeignId('captain_id');
            $table->dropConstrainedForeignId('boat_category_id');
            $table->dropConstrainedForeignId('boat_type_id');
            $table->dropColumn([
                'name_en', 'width_m', 'color', 'hull_number', 'body_type', 'engine_type', 'engine_power', 'engine_status',
                'call_sign', 'serial_number', 'capacity', 'license_process', 'license_area', 'license_date', 'next_inspection_date',
            ]);
        });

        foreach (array_reverse(self::LOOKUPS) as $lookup) {
            Schema::dropIfExists($lookup);
        }
    }
};
