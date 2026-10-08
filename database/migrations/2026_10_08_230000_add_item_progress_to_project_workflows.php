<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            $table->json('production_item_progress')->nullable();
            $table->json('qc_item_progress')->nullable();
            $table->json('qc_installation_item_progress')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('project_workflows', fn (Blueprint $table) => $table->dropColumn([
            'production_item_progress', 'qc_item_progress', 'qc_installation_item_progress',
        ]));
    }
};
