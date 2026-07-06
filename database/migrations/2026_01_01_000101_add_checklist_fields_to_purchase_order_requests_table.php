<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_order_requests', 'delivery_address')) {
                $table->text('delivery_address')->nullable()->after('customer_po_file');
            }
            if (! Schema::hasColumn('purchase_order_requests', 'delivery_pic_name')) {
                $table->string('delivery_pic_name')->nullable()->after('delivery_address');
            }
            if (! Schema::hasColumn('purchase_order_requests', 'delivery_pic_phone')) {
                $table->string('delivery_pic_phone', 50)->nullable()->after('delivery_pic_name');
            }
            if (! Schema::hasColumn('purchase_order_requests', 'npwp_name')) {
                $table->string('npwp_name')->nullable()->after('delivery_pic_phone');
            }
            if (! Schema::hasColumn('purchase_order_requests', 'npwp_number')) {
                $table->string('npwp_number', 100)->nullable()->after('npwp_name');
            }
            if (! Schema::hasColumn('purchase_order_requests', 'payment_term')) {
                $table->string('payment_term')->nullable()->after('npwp_number');
            }
            if (! Schema::hasColumn('purchase_order_requests', 'expected_delivery_date')) {
                $table->date('expected_delivery_date')->nullable()->after('payment_term');
            }
            if (! Schema::hasColumn('purchase_order_requests', 'checklist')) {
                $table->json('checklist')->nullable()->after('expected_delivery_date');
            }
            if (! Schema::hasColumn('purchase_order_requests', 'checklist_completed_at')) {
                $table->timestamp('checklist_completed_at')->nullable()->after('checklist');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_requests', function (Blueprint $table) {
            foreach ([
                'delivery_address', 'delivery_pic_name', 'delivery_pic_phone', 'npwp_name',
                'npwp_number', 'payment_term', 'expected_delivery_date', 'checklist', 'checklist_completed_at',
            ] as $column) {
                if (Schema::hasColumn('purchase_order_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
