<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('quotation_items', 'cost_price')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                $table->decimal('cost_price', 18, 2)->default(0)->after('unit');
            });

            // Pertahankan harga jual data lama. HPP diturunkan dari margin tersimpan.
            DB::table('quotation_items')->update([
                'cost_price' => DB::raw('unit_price * (1 - (margin / 100))'),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('quotation_items', 'cost_price')) {
            Schema::table('quotation_items', function (Blueprint $table) {
                $table->dropColumn('cost_price');
            });
        }
    }
};
