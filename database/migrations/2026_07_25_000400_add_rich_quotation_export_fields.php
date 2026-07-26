<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_request_items', function (Blueprint $table) {
            $table->string('quotation_image_path')->nullable()->after('specification');
            $table->string('quotation_image_name')->nullable()->after('quotation_image_path');
            $table->boolean('is_optional')->default(false)->after('margin');
            $table->unsignedInteger('sort_order')->default(0)->after('is_optional');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->foreignId('source_design_request_item_id')->nullable()->after('quotation_id')
                ->constrained('design_request_items')->nullOnDelete();
            $table->string('quotation_image_path')->nullable()->after('specification');
            $table->string('quotation_image_name')->nullable()->after('quotation_image_path');
            $table->boolean('is_optional')->default(false)->after('margin');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_design_request_item_id');
            $table->dropColumn(['quotation_image_path', 'quotation_image_name', 'is_optional']);
        });

        Schema::table('design_request_items', function (Blueprint $table) {
            $table->dropColumn(['quotation_image_path', 'quotation_image_name', 'is_optional', 'sort_order']);
        });
    }
};
