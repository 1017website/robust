<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Delivery Order (DO) tidak dipakai lagi. Daftar barangnya pindah ke form Delivery;
 * tabel delivery_orders dibiarkan sebagai arsip data lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            $table->json('delivery_items')->nullable();
        });

        if (! Schema::hasTable('delivery_orders')) {
            return;
        }
        DB::table('delivery_orders')->whereNotNull('items')->orderBy('id')->each(function ($order) {
            DB::table('project_workflows')->where('project_id', $order->project_id)->whereNull('delivery_items')
                ->update(['delivery_items' => $order->items]);
        });
    }

    public function down(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            $table->dropColumn('delivery_items');
        });
    }
};
