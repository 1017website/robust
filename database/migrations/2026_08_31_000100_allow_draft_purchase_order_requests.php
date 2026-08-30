<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Request PO kini bisa disimpan sebagai draf sebelum lengkap. Draf boleh belum
 * terhubung ke penawaran (khususnya untuk PO existing / non-CRM yang catatan
 * penawarannya baru dibuat saat request diajukan), sehingga quotation_id harus
 * nullable. Index unique tetap dipertahankan: MySQL mengizinkan banyak NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('purchase_order_requests', 'quotation_id')) {
            return;
        }

        Schema::table('purchase_order_requests', function (Blueprint $table) {
            $table->dropForeign(['quotation_id']);
        });

        Schema::table('purchase_order_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('quotation_id')->nullable()->change();
        });

        Schema::table('purchase_order_requests', function (Blueprint $table) {
            $table->foreign('quotation_id')->references('id')->on('quotations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('purchase_order_requests', 'quotation_id')) {
            return;
        }

        Schema::table('purchase_order_requests', function (Blueprint $table) {
            $table->dropForeign(['quotation_id']);
        });

        Schema::table('purchase_order_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('quotation_id')->nullable(false)->change();
        });

        Schema::table('purchase_order_requests', function (Blueprint $table) {
            $table->foreign('quotation_id')->references('id')->on('quotations')->cascadeOnDelete();
        });
    }
};
