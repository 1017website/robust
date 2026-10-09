<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            $table->string('qc_result')->nullable();
            $table->string('qc_installation_result')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('project_workflows', function (Blueprint $table) {
            $table->dropColumn(['qc_result', 'qc_installation_result']);
        });
    }
};
