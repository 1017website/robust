<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            $table->date('production_target_date')->nullable();
            $table->date('qc_target_date')->nullable();
            $table->date('qc_installation_target_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            $table->dropColumn(['production_target_date', 'qc_target_date', 'qc_installation_target_date']);
        });
    }
};
